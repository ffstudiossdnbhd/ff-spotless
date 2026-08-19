<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCollectionRequest;
use App\Models\MonthlyTaskTemplate;
use App\Models\TaskCollection;
use App\Models\TaskTemplate;
use App\Models\WeeklyTaskTemplate;
use App\Services\AuditLogger;
use App\Services\ChecklistMaterializer;
use App\Services\MonthlyTaskScheduler;
use App\Services\OperationalDate;
use App\Services\WeeklyTaskScheduler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TaskCollectionController extends Controller
{
    public function store(
        StoreTaskCollectionRequest $request,
        ChecklistMaterializer $daily,
        WeeklyTaskScheduler $weekly,
        MonthlyTaskScheduler $monthly,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $rotation = TaskCollection::query()->create([
            'name' => $request->validated('name'),
            'is_default' => false,
            'rotation_order' => (int) TaskCollection::query()->max('rotation_order') + 1,
        ]);
        $daily->refreshMaterializedDatesFrom($dates->today());
        $weekly->refreshMaterializedWeeksFrom($dates->today());
        $monthly->refreshMaterializedMonthsFrom($dates->today());
        $audits->admin('rotation.created', $rotation, ['name' => $rotation->name]);

        return to_route('admin.index');
    }

    public function destroy(
        TaskCollection $taskCollection,
        ChecklistMaterializer $daily,
        WeeklyTaskScheduler $weekly,
        MonthlyTaskScheduler $monthly,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        if ($taskCollection->is_default) {
            throw ValidationException::withMessages([
                'collection' => 'The default rotation cannot be deleted.',
            ]);
        }

        $isUsedBySchedule = $taskCollection->schedules()->exists();

        $isUsedByActiveDaily = $taskCollection->dailyTemplates()->where('is_active', true)->exists()
            || DB::table('task_template_task_collection')
                ->join('task_templates', 'task_templates.id', '=', 'task_template_task_collection.task_template_id')
                ->where('task_template_task_collection.task_collection_id', $taskCollection->getKey())
                ->where('task_templates.is_active', true)
                ->exists();

        $isUsedByActiveWeekly = $taskCollection->weeklyTemplates()->where('is_active', true)->exists()
            || DB::table('weekly_task_template_task_collection')
                ->join('weekly_task_templates', 'weekly_task_templates.id', '=', 'weekly_task_template_task_collection.weekly_task_template_id')
                ->where('weekly_task_template_task_collection.task_collection_id', $taskCollection->getKey())
                ->where('weekly_task_templates.is_active', true)
                ->exists();

        $isUsedByActiveMonthly = Schema::hasTable('monthly_task_template_task_collection')
            && DB::table('monthly_task_template_task_collection')
                ->join('monthly_task_templates', 'monthly_task_templates.id', '=', 'monthly_task_template_task_collection.monthly_task_template_id')
                ->where('monthly_task_template_task_collection.task_collection_id', $taskCollection->getKey())
                ->where('monthly_task_templates.is_active', true)
                ->exists();

        if ($isUsedBySchedule || $isUsedByActiveDaily || $isUsedByActiveWeekly || $isUsedByActiveMonthly) {
            throw ValidationException::withMessages([
                'collection' => 'This rotation is still used by active schedules or tasks. Remove those links first.',
            ]);
        }

        DB::transaction(function () use ($taskCollection): void {
            // Null out legacy foreign keys on archived/inactive tasks so restrict foreign key doesn't block deletion
            TaskTemplate::query()
                ->where('task_collection_id', $taskCollection->getKey())
                ->update(['task_collection_id' => null]);

            WeeklyTaskTemplate::query()
                ->where('task_collection_id', $taskCollection->getKey())
                ->update(['task_collection_id' => null]);

            if (Schema::hasColumn('monthly_task_templates', 'task_collection_id')) {
                MonthlyTaskTemplate::query()
                    ->where('task_collection_id', $taskCollection->getKey())
                    ->update(['task_collection_id' => null]);
            }

            $taskCollection->delete();
        }, 3);

        $daily->refreshMaterializedDatesFrom($dates->today());
        $weekly->refreshMaterializedWeeksFrom($dates->today());
        $monthly->refreshMaterializedMonthsFrom($dates->today());
        $audits->admin('rotation.deleted', $taskCollection, ['name' => $taskCollection->name]);

        return to_route('admin.index');
    }
}
