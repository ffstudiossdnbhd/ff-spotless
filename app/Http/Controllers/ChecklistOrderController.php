<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderChecklistItemsRequest;
use App\Services\AuditLogger;
use App\Services\ChecklistOrderingService;

class ChecklistOrderController extends Controller
{
    public function store(ReorderChecklistItemsRequest $request, ChecklistOrderingService $service, AuditLogger $audits)
    {
        $data = $request->validated();

        $service->reorder($data['date'], (int) $data['task_session_id'], $data['items']);
        $audits->cleaner('checklist.reordered', null, [
            'date' => $data['date'],
            'task_session_id' => (int) $data['task_session_id'],
            'item_count' => count($data['items']),
        ]);

        return to_route('checklist.index', ['date' => $data['date']]);
    }
}
