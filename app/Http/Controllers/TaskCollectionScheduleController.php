<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCollectionScheduleRequest;
use App\Models\TaskCollectionSchedule;
use App\Services\ChecklistMaterializer;
use App\Services\AuditLogger;
use App\Services\OperationalDate;
use App\Services\WeeklyTaskScheduler;
use Carbon\CarbonImmutable;

class TaskCollectionScheduleController extends Controller
{
    public function store(
        StoreTaskCollectionScheduleRequest $request,
        ChecklistMaterializer $daily,
        WeeklyTaskScheduler $weekly,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $data = $request->validated();
        $daily->catchUpThrough($dates->today());
        $weekly->advanceThrough($dates->today());
        $schedule = TaskCollectionSchedule::query()->create($data);
        $start = $dates->fromDateString($schedule->starts_on->toDateString())->startOfWeek(CarbonImmutable::MONDAY);

        $daily->refreshMaterializedDatesFrom($start);
        $weekly->refreshMaterializedWeeksFrom($start);
        $audits->admin('legacy_rotation_schedule.created', $schedule, [
            'rotation_id' => $schedule->task_collection_id,
            'starts_on' => $schedule->starts_on->toDateString(),
            'ends_on' => $schedule->ends_on->toDateString(),
        ]);

        return to_route('admin.index');
    }

    public function destroy(
        TaskCollectionSchedule $taskCollectionSchedule,
        ChecklistMaterializer $daily,
        WeeklyTaskScheduler $weekly,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $daily->catchUpThrough($dates->today());
        $weekly->advanceThrough($dates->today());
        $start = $taskCollectionSchedule->starts_on->startOfWeek(CarbonImmutable::MONDAY);

        $taskCollectionSchedule->delete();
        $daily->refreshMaterializedDatesFrom($start);
        $weekly->refreshMaterializedWeeksFrom($start);
        $audits->admin('legacy_rotation_schedule.deleted', null, [
            'rotation_id' => $taskCollectionSchedule->task_collection_id,
            'starts_on' => $taskCollectionSchedule->starts_on->toDateString(),
            'ends_on' => $taskCollectionSchedule->ends_on->toDateString(),
        ]);

        return to_route('admin.index');
    }
}
