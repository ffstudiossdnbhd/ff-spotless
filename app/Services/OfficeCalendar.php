<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class OfficeCalendar
{
    /**
     * @var Collection<string, PublicHoliday>|null
     */
    private ?Collection $holidays = null;

    public function __construct(private readonly OperationalDate $dates) {}

    public function isPublicHoliday(CarbonImmutable|string $date): bool
    {
        return $this->holidays()->has($this->dateString($date));
    }

    public function publicHoliday(CarbonImmutable|string $date): ?PublicHoliday
    {
        return $this->holidays()->get($this->dateString($date));
    }

    public function isWorkingDay(CarbonImmutable|string $date): bool
    {
        return $this->dates->isWorkingDay($date) && ! $this->isPublicHoliday($date);
    }

    public function nextWorkingDayAfter(CarbonImmutable $date): CarbonImmutable
    {
        $cursor = $date->addDay()->startOfDay();

        while (! $this->isWorkingDay($cursor)) {
            $cursor = $cursor->addDay();
        }

        return $cursor;
    }

    /**
     * @return Collection<string, PublicHoliday>
     */
    public function holidays(): Collection
    {
        if ($this->holidays === null) {
            $this->holidays = Schema::hasTable('public_holidays')
                ? PublicHoliday::query()
                    ->orderBy('date')
                    ->get()
                    ->keyBy(static fn (PublicHoliday $holiday): string => $holiday->date->toDateString())
                : collect();
        }

        return $this->holidays;
    }

    private function dateString(CarbonImmutable|string $date): string
    {
        return is_string($date) ? $date : $date->toDateString();
    }
}
