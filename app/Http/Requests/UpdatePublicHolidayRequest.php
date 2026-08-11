<?php

namespace App\Http\Requests;

use App\Models\PublicHoliday;

class UpdatePublicHolidayRequest extends StorePublicHolidayRequest
{
    protected function ignoredPublicHolidayId(): ?int
    {
        $holiday = $this->route('publicHoliday');

        return $holiday instanceof PublicHoliday ? (int) $holiday->getKey() : null;
    }
}
