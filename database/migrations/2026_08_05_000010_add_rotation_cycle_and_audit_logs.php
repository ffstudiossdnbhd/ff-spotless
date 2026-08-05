<?php

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_collections', function (Blueprint $table): void {
            $table->unsignedInteger('rotation_order')->nullable()->after('is_default');
            $table->index('rotation_order');
        });

        Schema::create('rotation_cycle_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->date('anchor_week_start');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 100);
            $table->string('actor_type', 30);
            $table->string('actor_label', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at', 6);

            $table->index('occurred_at');
            $table->index(['action', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        $now = now('UTC');
        $timezone = (string) config('checklist.timezone', 'Asia/Kuala_Lumpur');
        $anchor = CarbonImmutable::now($timezone)
            ->startOfWeek(CarbonInterface::SUNDAY)
            ->toDateString();

        DB::table('rotation_cycle_settings')->insert([
            'id' => 1,
            'anchor_week_start' => $anchor,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('task_collections')
            ->where('is_default', false)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->each(static function ($id, int $index): void {
                DB::table('task_collections')
                    ->where('id', $id)
                    ->update(['rotation_order' => $index + 1]);
            });

        DB::table('weekly_task_templates')
            ->where('is_active', true)
            ->whereIn('due_weekday', [6, 7])
            ->update(['due_weekday' => CarbonInterface::FRIDAY]);

        $today = CarbonImmutable::now($timezone)->startOfDay();
        // Future, incomplete snapshots were selected by the retired manual schedule.
        // Recreate them lazily under the Sunday-based rotation resolver; completed
        // records are deliberately retained as history.
        $futureDailyIds = DB::table('daily_checklists')
            ->where('is_completed', false)
            ->whereDate('date', '>=', $today->toDateString())
            ->orderBy('id')
            ->get(['id', 'date'])
            ->pluck('id');

        if ($futureDailyIds->isNotEmpty()) {
            DB::table('checklist_item_positions')
                ->where('item_type', 'daily')
                ->whereIn('item_id', $futureDailyIds)
                ->delete();
            DB::table('daily_checklists')->whereIn('id', $futureDailyIds)->delete();
        }

        DB::table('checklist_materializations')
            ->whereDate('date', '>=', $today->toDateString())
            ->delete();

        $pendingWeeklyIds = DB::table('weekly_task_occurrences')
            ->where('status', 'pending')
            ->whereDate('week_start', '>=', $today->startOfWeek(CarbonInterface::MONDAY)->toDateString())
            ->pluck('id');

        if ($pendingWeeklyIds->isNotEmpty()) {
            DB::table('checklist_item_positions')
                ->where('item_type', 'weekly')
                ->whereIn('item_id', $pendingWeeklyIds)
                ->delete();
            DB::table('weekly_task_postponements')
                ->whereIn('weekly_task_occurrence_id', $pendingWeeklyIds)
                ->delete();
            DB::table('weekly_task_occurrences')->whereIn('id', $pendingWeeklyIds)->delete();
        }

        DB::table('weekly_materializations')
            ->whereDate('week_start', '>=', $today->startOfWeek(CarbonInterface::MONDAY)->toDateString())
            ->delete();

        DB::table('task_reopen_audits')
            ->orderBy('id')
            ->chunkById(100, static function ($audits): void {
                $rows = $audits->map(static function ($audit): array {
                    return [
                        'action' => 'task.reopened',
                        'actor_type' => 'admin',
                        'actor_label' => (string) $audit->performed_by,
                        'subject_type' => 'task.'.$audit->task_type,
                        'subject_id' => $audit->task_id,
                        'metadata' => json_encode([
                            'task_name' => $audit->task_name,
                            'session_name' => $audit->session_name,
                            'task_date' => (string) $audit->task_date,
                            'reason' => $audit->reason,
                            'invalidated_evidence_count' => (int) $audit->invalidated_evidence_count,
                        ], JSON_THROW_ON_ERROR),
                        'occurred_at' => $audit->occurred_at,
                    ];
                })->all();

                if ($rows !== []) {
                    DB::table('audit_logs')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('rotation_cycle_settings');

        Schema::table('task_collections', function (Blueprint $table): void {
            $table->dropIndex(['rotation_order']);
            $table->dropColumn('rotation_order');
        });
    }
};
