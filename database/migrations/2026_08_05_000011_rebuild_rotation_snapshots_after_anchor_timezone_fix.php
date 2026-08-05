<?php

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebuild incomplete snapshots generated before the rotation anchor was
     * interpreted in the operational timezone.
     */
    public function up(): void
    {
        $timezone = (string) config('checklist.timezone', 'Asia/Kuala_Lumpur');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $weekStart = $today->startOfWeek(CarbonInterface::MONDAY);

        DB::transaction(function () use ($today, $weekStart): void {
            $dailyIds = DB::table('daily_checklists')
                ->where('is_completed', false)
                ->whereDate('date', '>=', $today->toDateString())
                ->orderBy('id')
                ->pluck('id');

            if ($dailyIds->isNotEmpty()) {
                DB::table('checklist_item_positions')
                    ->where('item_type', 'daily')
                    ->whereIn('item_id', $dailyIds)
                    ->delete();

                DB::table('daily_checklists')->whereIn('id', $dailyIds)->delete();
            }

            DB::table('checklist_materializations')
                ->whereDate('date', '>=', $today->toDateString())
                ->delete();

            $weeklyIds = DB::table('weekly_task_occurrences')
                ->where('status', 'pending')
                ->whereDate('week_start', '>=', $weekStart->toDateString())
                ->orderBy('id')
                ->pluck('id');

            if ($weeklyIds->isNotEmpty()) {
                DB::table('checklist_item_positions')
                    ->where('item_type', 'weekly')
                    ->whereIn('item_id', $weeklyIds)
                    ->delete();

                DB::table('weekly_task_postponements')
                    ->whereIn('weekly_task_occurrence_id', $weeklyIds)
                    ->delete();

                DB::table('weekly_task_occurrences')->whereIn('id', $weeklyIds)->delete();
            }

            DB::table('weekly_materializations')
                ->whereDate('week_start', '>=', $weekStart->toDateString())
                ->delete();
        }, 3);
    }

    /**
     * Snapshot reconstruction is intentionally one-way so completed history
     * and its supporting evidence are never recreated or removed on rollback.
     */
    public function down(): void
    {
        // No schema change to reverse.
    }
};
