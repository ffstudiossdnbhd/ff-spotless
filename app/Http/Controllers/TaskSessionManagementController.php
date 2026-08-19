<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskSessionRequest;
use App\Models\MonthlyTaskOccurrence;
use App\Models\TaskSession;
use App\Models\WeeklyTaskOccurrence;
use App\Services\ChecklistMaterializer;
use App\Services\AuditLogger;
use App\Services\MonthlyTaskScheduler;
use App\Services\OperationalDate;
use App\Services\WeeklyTaskScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskSessionManagementController extends Controller
{
    public function store(StoreTaskSessionRequest $request, AuditLogger $audits)
    {
        $startTime = $request->validated('start_time');
        $endTime = $request->validated('end_time');
        $name = TaskSession::formatSessionName($startTime, $endTime);

        $session = TaskSession::query()->create([
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'sort_order' => (int) TaskSession::query()->max('sort_order') + 1,
            'is_active' => true,
        ]);
        $audits->admin('session.created', $session, ['name' => $session->name, 'start_time' => $startTime, 'end_time' => $endTime]);

        return to_route('admin.index');
    }

    public function update(
        StoreTaskSessionRequest $request,
        TaskSession $taskSession,
        ChecklistMaterializer $materializer,
        OperationalDate $dates,
        WeeklyTaskScheduler $weekly,
        MonthlyTaskScheduler $monthly,
        AuditLogger $audits,
    ) {
        $materializer->catchUpThrough($dates->today());
        $weekly->advanceThrough($dates->today());
        $monthly->advanceThrough($dates->today());

        $startTime = $request->validated('start_time');
        $endTime = $request->validated('end_time');
        $name = TaskSession::formatSessionName($startTime, $endTime);

        DB::transaction(function () use ($taskSession, $name, $startTime, $endTime, $materializer, $dates): void {
            $taskSession->forceFill([
                'name' => $name,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ])->save();

            $materializer->renameSessionSnapshots($taskSession->id, $name);

            WeeklyTaskOccurrence::query()
                ->where('task_session_id', $taskSession->id)
                ->where('status', 'pending')
                ->whereDate('week_start', '>=', $dates->today()->startOfWeek()->toDateString())
                ->update(['session_name' => $name]);

            MonthlyTaskOccurrence::query()
                ->where('task_session_id', $taskSession->id)
                ->where('status', 'pending')
                ->whereDate('month_start', '>=', $dates->today()->startOfMonth()->toDateString())
                ->update(['session_name' => $name]);
        }, 3);

        $audits->admin('session.updated', $taskSession, ['name' => $name, 'start_time' => $startTime, 'end_time' => $endTime]);

        return to_route('admin.index');
    }

    public function destroy(Request $request, TaskSession $taskSession, AuditLogger $audits)
    {
        if (! $taskSession->is_active) {
            return to_route('admin.index');
        }

        if (TaskSession::query()->active()->count() <= 1) {
            throw ValidationException::withMessages(['session' => 'At least one active session must be kept.']);
        }

        if (
            $taskSession->taskTemplates()->active()->exists()
            || $taskSession->weeklyTaskTemplates()->active()->exists()
            || $taskSession->monthlyTaskTemplates()->active()->exists()
        ) {
            throw ValidationException::withMessages(['session' => 'Move every active task before archiving this session.']);
        }

        $taskSession->forceFill(['is_active' => false])->save();
        $audits->admin('session.archived', $taskSession, ['name' => $taskSession->name]);

        return to_route('admin.index');
    }
}
