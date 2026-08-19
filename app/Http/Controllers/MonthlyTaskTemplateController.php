<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyTaskTemplateRequest;
use App\Http\Requests\UpdateMonthlyTaskTemplateRequest;
use App\Models\MonthlyTaskTemplate;
use App\Models\TaskSession;
use App\Services\ChecklistMaterializer;
use App\Services\AuditLogger;
use App\Services\MonthlyTaskScheduler;
use App\Services\OperationalDate;
use Illuminate\Validation\ValidationException;

class MonthlyTaskTemplateController extends Controller
{
    public function store(
        StoreMonthlyTaskTemplateRequest $request,
        ChecklistMaterializer $daily,
        MonthlyTaskScheduler $scheduler,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $data = $request->validated();
        $daily->catchUpThrough($dates->today());
        $scheduler->advanceThrough($dates->today());
        $session = TaskSession::query()->active()->find($data['task_session_id']);

        if ($session === null) {
            throw ValidationException::withMessages(['task_session_id' => 'Task session is not active.']);
        }

        $today = $dates->today();
        $startsOn = $today->startOfMonth();

        $collectionIds = $data['applies_to_all_collections'] ? [] : array_values(array_unique($data['task_collection_ids'] ?? []));

        $template = MonthlyTaskTemplate::query()->create([
            'task_name' => $data['task_name'],
            'description' => $data['description'] ?? null,
            'task_session_id' => $data['task_session_id'],
            'task_collection_id' => $collectionIds[0] ?? null,
            'applies_to_all_collections' => $data['applies_to_all_collections'],
            'finish_time' => $data['finish_time'],
            'sort_order' => (int) MonthlyTaskTemplate::query()->max('sort_order') + 1,
            'starts_on' => $startsOn->toDateString(),
            'is_active' => true,
        ]);
        $template->taskCollections()->sync($collectionIds);

        $scheduler->materializeMonth($startsOn, true);
        $scheduler->refreshMaterializedMonthsFrom($startsOn);
        $audits->admin('task_template.created', $template, [
            'task_type' => 'monthly',
            'task_name' => $template->task_name,
        ]);

        return to_route('admin.index');
    }

    public function update(
        UpdateMonthlyTaskTemplateRequest $request,
        MonthlyTaskTemplate $monthlyTaskTemplate,
        ChecklistMaterializer $daily,
        MonthlyTaskScheduler $scheduler,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $data = $request->validated();
        $daily->catchUpThrough($dates->today());
        $scheduler->advanceThrough($dates->today());

        if (! $monthlyTaskTemplate->is_active || ! TaskSession::query()->active()->whereKey($data['task_session_id'])->exists()) {
            throw ValidationException::withMessages(['task' => 'Monthly template or session is not active.']);
        }

        $collectionIds = $data['applies_to_all_collections'] ? [] : array_values(array_unique($data['task_collection_ids'] ?? []));

        $monthlyTaskTemplate->forceFill([
            'task_name' => $data['task_name'],
            'description' => $data['description'] ?? null,
            'task_session_id' => $data['task_session_id'],
            'task_collection_id' => $collectionIds[0] ?? null,
            'applies_to_all_collections' => $data['applies_to_all_collections'],
            'finish_time' => $data['finish_time'],
        ])->save();
        $monthlyTaskTemplate->taskCollections()->sync($collectionIds);
        $scheduler->updateTemplateSnapshots($monthlyTaskTemplate);
        $audits->admin('task_template.updated', $monthlyTaskTemplate, [
            'task_type' => 'monthly',
            'task_name' => $data['task_name'],
        ]);

        return to_route('admin.index');
    }

    public function destroy(
        MonthlyTaskTemplate $monthlyTaskTemplate,
        ChecklistMaterializer $daily,
        MonthlyTaskScheduler $scheduler,
        OperationalDate $dates,
        AuditLogger $audits,
    ) {
        $daily->catchUpThrough($dates->today());
        $scheduler->advanceThrough($dates->today());
        $scheduler->deactivateTemplate($monthlyTaskTemplate);
        $audits->admin('task_template.archived', $monthlyTaskTemplate, [
            'task_type' => 'monthly',
            'task_name' => $monthlyTaskTemplate->task_name,
        ]);

        return to_route('admin.index');
    }
}
