<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update task_sessions with start_time and end_time
        Schema::table('task_sessions', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('name');
            $table->time('end_time')->nullable()->after('start_time');
            $table->index('start_time');
        });

        // Migrate existing session times based on known names
        $sessions = DB::table('task_sessions')->get();
        foreach ($sessions as $session) {
            $name = (string) $session->name;
            $start = '09:00:00';
            $end = '11:00:00';

            if (preg_match('/^(\d{1,2}:\d{2}\s*(?:AM|PM))\s*-\s*(\d{1,2}:\d{2}\s*(?:AM|PM))$/i', trim($name), $matches)) {
                try {
                    $start = CarbonImmutable::parse($matches[1])->format('H:i:s');
                    $end = CarbonImmutable::parse($matches[2])->format('H:i:s');
                } catch (\Throwable) {}
            } elseif ($name === 'Pagi') {
                $start = '09:00:00';
                $end = '11:00:00';
            } elseif ($name === 'Tengah Hari') {
                $start = '11:00:00';
                $end = '13:00:00';
            } elseif ($name === 'Petang') {
                $start = '14:00:00';
                $end = '16:00:00';
            }

            $formattedName = CarbonImmutable::parse($start)->format('g:i A') . ' - ' . CarbonImmutable::parse($end)->format('g:i A');

            DB::table('task_sessions')->where('id', $session->id)->update([
                'start_time' => $start,
                'end_time' => $end,
                'name' => $formattedName,
            ]);
        }

        Schema::table('task_sessions', function (Blueprint $table) {
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->dropUnique('task_sessions_name_unique');
        });

        // 2. Add finish_time and description to task_templates, weekly_task_templates, daily_checklists, weekly_task_occurrences
        Schema::table('task_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('task_name');
            $table->time('finish_time')->nullable()->after('applies_to_all_collections');
        });

        Schema::table('weekly_task_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('task_name');
            $table->time('finish_time')->nullable()->after('applies_to_all_collections');
        });

        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->text('description')->nullable()->after('task_name');
            $table->time('finish_time')->nullable()->after('session_name');
        });

        Schema::table('weekly_task_occurrences', function (Blueprint $table) {
            $table->text('description')->nullable()->after('task_name');
            $table->time('finish_time')->nullable()->after('session_name');
        });

        // Populate default finish_times from parent session end_time
        $sessionEndTimes = DB::table('task_sessions')->pluck('end_time', 'id');

        foreach ($sessionEndTimes as $sessionId => $endTime) {
            DB::table('task_templates')->where('task_session_id', $sessionId)->update(['finish_time' => $endTime]);
            DB::table('weekly_task_templates')->where('task_session_id', $sessionId)->update(['finish_time' => $endTime]);
            DB::table('daily_checklists')->where('task_session_id', $sessionId)->update(['finish_time' => $endTime]);
            DB::table('weekly_task_occurrences')->where('task_session_id', $sessionId)->update(['finish_time' => $endTime]);
        }

        DB::table('task_templates')->whereNull('finish_time')->update(['finish_time' => '11:00:00']);
        DB::table('weekly_task_templates')->whereNull('finish_time')->update(['finish_time' => '11:00:00']);
        DB::table('daily_checklists')->whereNull('finish_time')->update(['finish_time' => '11:00:00']);
        DB::table('weekly_task_occurrences')->whereNull('finish_time')->update(['finish_time' => '11:00:00']);

        Schema::table('task_templates', function (Blueprint $table) {
            $table->time('finish_time')->nullable(false)->change();
            $table->dropColumn('credit_hours');
        });

        Schema::table('weekly_task_templates', function (Blueprint $table) {
            $table->time('finish_time')->nullable(false)->change();
            $table->dropColumn('credit_hours');
        });

        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->time('finish_time')->nullable(false)->change();
            $table->dropColumn('credit_hours');
        });

        Schema::table('weekly_task_occurrences', function (Blueprint $table) {
            $table->time('finish_time')->nullable(false)->change();
            $table->dropColumn('credit_hours');
        });

        // 3. Create Monthly Task structures
        Schema::create('monthly_task_templates', function (Blueprint $table) {
            $table->id();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->foreignId('task_session_id')->constrained('task_sessions')->restrictOnDelete();
            $table->foreignId('task_collection_id')->nullable()->constrained('task_collections')->restrictOnDelete();
            $table->boolean('applies_to_all_collections')->default(true);
            $table->time('finish_time');
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('starts_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'task_session_id']);
            $table->index(['is_active', 'starts_on']);
            $table->index(['is_active', 'task_collection_id']);
            $table->index('applies_to_all_collections');
        });

        Schema::create('monthly_task_template_task_collection', function (Blueprint $table) {
            $table->unsignedBigInteger('monthly_task_template_id');
            $table->unsignedBigInteger('task_collection_id');

            $table->primary(['monthly_task_template_id', 'task_collection_id'], 'mtttc_primary');
            $table->foreign('monthly_task_template_id', 'mtttc_template_fk')->references('id')->on('monthly_task_templates')->cascadeOnDelete();
            $table->foreign('task_collection_id', 'mtttc_collection_fk')->references('id')->on('task_collections')->cascadeOnDelete();
        });

        Schema::create('monthly_materializations', function (Blueprint $table) {
            $table->date('month_start')->primary();
        });

        Schema::create('monthly_task_occurrences', function (Blueprint $table) {
            $table->id();
            $table->date('month_start');
            $table->foreignId('monthly_task_template_id')->constrained('monthly_task_templates')->restrictOnDelete();
            $table->foreignId('task_session_id')->constrained('task_sessions')->restrictOnDelete();
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->string('session_name', 100);
            $table->time('finish_time');
            $table->date('original_due_date');
            $table->date('scheduled_date');
            $table->string('status', 20)->default('pending');
            $table->string('missed_reason', 20)->nullable();
            $table->timestamp('completed_at', 6)->nullable();
            $table->date('completed_on')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('completion_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['month_start', 'monthly_task_template_id'], 'monthly_occurrence_unique');
            $table->index(['scheduled_date', 'status']);
            $table->index(['completed_on', 'status']);
            $table->index(['month_start', 'status']);
        });

        Schema::create('monthly_task_postponements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_task_occurrence_id')->constrained('monthly_task_occurrences')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('reason', 20);
            $table->timestamps();

            $table->unique(['monthly_task_occurrence_id', 'from_date'], 'monthly_postponement_unique');
            $table->index(['from_date', 'reason']);
        });

        Schema::create('monthly_task_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_task_occurrence_id')->constrained('monthly_task_occurrences')->cascadeOnDelete();
            $table->string('disk', 50);
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();

            $table->index('monthly_task_occurrence_id');
        });

        // 4. Drop drag-and-drop ordering table
        Schema::dropIfExists('checklist_item_positions');
    }

    public function down(): void
    {
        Schema::create('checklist_item_positions', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('task_session_id')->constrained('task_sessions')->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->unsignedBigInteger('item_id');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['date', 'item_type', 'item_id'], 'checklist_item_position_unique');
            $table->unique(['date', 'task_session_id', 'position'], 'checklist_session_position_unique');
        });

        Schema::dropIfExists('monthly_task_evidence');
        Schema::dropIfExists('monthly_task_postponements');
        Schema::dropIfExists('monthly_task_occurrences');
        Schema::dropIfExists('monthly_materializations');
        Schema::dropIfExists('monthly_task_template_task_collection');
        Schema::dropIfExists('monthly_task_templates');

        Schema::table('weekly_task_occurrences', function (Blueprint $table) {
            $table->decimal('credit_hours', 6, 2)->default(1)->after('session_name');
            $table->dropColumn(['finish_time', 'description']);
        });

        Schema::table('daily_checklists', function (Blueprint $table) {
            $table->decimal('credit_hours', 6, 2)->default(1)->after('session_name');
            $table->dropColumn(['finish_time', 'description']);
        });

        Schema::table('weekly_task_templates', function (Blueprint $table) {
            $table->decimal('credit_hours', 6, 2)->default(1)->after('applies_to_all_collections');
            $table->dropColumn(['finish_time', 'description']);
        });

        Schema::table('task_templates', function (Blueprint $table) {
            $table->decimal('credit_hours', 6, 2)->default(1)->after('applies_to_all_collections');
            $table->dropColumn(['finish_time', 'description']);
        });

        Schema::table('task_sessions', function (Blueprint $table) {
            $table->dropIndex(['start_time']);
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
