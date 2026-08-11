<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicHolidayRequest;
use App\Http\Requests\UpdatePublicHolidayRequest;
use App\Models\PublicHoliday;
use App\Services\AuditLogger;
use App\Services\OperationalDate;
use App\Services\PublicHolidayReconciler;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPublicHolidayController extends Controller
{
    public function store(
        StorePublicHolidayRequest $request,
        PublicHolidayReconciler $reconciler,
        AuditLogger $audits,
    ) {
        $data = $request->validated();

        $holiday = DB::transaction(function () use ($data, $reconciler): PublicHoliday {
            $holiday = PublicHoliday::query()->create([
                'date' => $data['date'],
                'name' => $data['name'],
            ]);

            $reconciler->reconcile();

            return $holiday;
        }, 3);

        $audits->admin('public_holiday.created', $holiday, [
            'date' => $holiday->date->toDateString(),
            'name' => $holiday->name,
        ]);

        return to_route('admin.index');
    }

    public function update(
        UpdatePublicHolidayRequest $request,
        PublicHoliday $publicHoliday,
        OperationalDate $dates,
        PublicHolidayReconciler $reconciler,
        AuditLogger $audits,
    ) {
        $data = $request->validated();

        /** @var array{holiday: PublicHoliday, previousDate: string, previousName: string} $result */
        $result = DB::transaction(function () use ($data, $publicHoliday, $dates, $reconciler): array {
            $holiday = PublicHoliday::query()->lockForUpdate()->findOrFail($publicHoliday->getKey());
            $this->ensureEditable($holiday, $dates);

            $previousDate = $holiday->date->toDateString();
            $previousName = $holiday->name;
            $holiday->forceFill([
                'date' => $data['date'],
                'name' => $data['name'],
            ])->save();

            $reconciler->reconcile();

            return [
                'holiday' => $holiday,
                'previousDate' => $previousDate,
                'previousName' => $previousName,
            ];
        }, 3);

        $holiday = $result['holiday'];
        $audits->admin('public_holiday.updated', $holiday, [
            'date' => $holiday->date->toDateString(),
            'name' => $holiday->name,
            'previous_date' => $result['previousDate'],
            'previous_name' => $result['previousName'],
        ]);

        return to_route('admin.index');
    }

    public function destroy(
        PublicHoliday $publicHoliday,
        OperationalDate $dates,
        PublicHolidayReconciler $reconciler,
        AuditLogger $audits,
    ) {
        $holiday = DB::transaction(function () use ($publicHoliday, $dates, $reconciler): PublicHoliday {
            $holiday = PublicHoliday::query()->lockForUpdate()->findOrFail($publicHoliday->getKey());
            $this->ensureEditable($holiday, $dates);

            $holiday->delete();
            $reconciler->reconcile();

            return $holiday;
        }, 3);

        $audits->admin('public_holiday.deleted', $holiday, [
            'date' => $holiday->date->toDateString(),
            'name' => $holiday->name,
        ]);

        return to_route('admin.index');
    }

    private function ensureEditable(PublicHoliday $holiday, OperationalDate $dates): void
    {
        if ($holiday->date->toDateString() <= $dates->today()->toDateString()) {
            throw ValidationException::withMessages([
                'date' => 'This public holiday can no longer be changed.',
            ]);
        }
    }
}
