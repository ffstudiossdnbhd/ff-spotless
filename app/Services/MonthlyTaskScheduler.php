<?php

namespace App\Services;

use App\Models\ChecklistDayStatus;
use App\Models\MonthlyTaskOccurrence;
use App\Models\MonthlyTaskPostponement;
use App\Models\MonthlyTaskTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyTaskScheduler
{
    public function __construct(
        private readonly ChecklistMaterializer $materializer,
        private readonly OperationalDate $dates,
        private readonly OfficeCalendar $calendar,
        private readonly TaskCollectionResolver $collections,
    ) {}

    public function lastFridayOfMonth(CarbonImmutable $date): CarbonImmutable
    {
        $end = $date->endOfMonth()->startOfDay();

        return $end->isFriday() ? $end : $end->previous(CarbonInterface::FRIDAY);
    }

    public function materializeMonth(CarbonImmutable $date, bool $refresh = false): void
    {
        $monthStart = $date->startOfMonth();
        $monthEnd = $date->endOfMonth();

        DB::transaction(function () use ($monthStart, $monthEnd, $refresh): void {
            $this->materializer->acquireTemplateSynchronizationLock();
            $alreadyMaterialized = DB::table('monthly_materializations')
                ->whereDate('month_start', $monthStart->toDateString())
                ->exists();

            if ($alreadyMaterialized && ! $refresh) {
                return;
            }

            DB::table('monthly_materializations')->insertOrIgnore([
                'month_start' => $monthStart->toDateString(),
            ]);

            $templates = MonthlyTaskTemplate::query()
                ->active()
                ->whereDate('starts_on', '<=', $monthEnd->toDateString())
                ->with(['taskSession:id,name', 'taskCollections:id,name'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->filter(function (MonthlyTaskTemplate $template) use ($monthStart): bool {
                    if ($template->applies_to_all_collections) {
                        return true;
                    }

                    $lastFriday = $this->lastFridayOfMonth($monthStart);
                    $activeCollection = $this->collections->forDate($lastFriday);

                    return $template->taskCollections->contains('id', $activeCollection->getKey());
                })
                ->values();

            if ($refresh) {
                $staleQuery = MonthlyTaskOccurrence::query()
                    ->whereDate('month_start', $monthStart->toDateString())
                    ->where('status', 'pending');

                $staleIds = $templates->isEmpty()
                    ? $staleQuery->pluck('id')
                    : $staleQuery->whereNotIn('monthly_task_template_id', $templates->pluck('id'))->pluck('id');

                if ($staleIds->isNotEmpty()) {
                    MonthlyTaskPostponement::query()
                        ->whereIn('monthly_task_occurrence_id', $staleIds)
                        ->delete();

                    MonthlyTaskOccurrence::query()->whereKey($staleIds)->delete();
                }
            }

            $templates->each(function (MonthlyTaskTemplate $template) use ($monthStart): void {
                $this->syncTemplateOccurrence($template, $monthStart);
            });
        }, 3);
    }

    public function advanceThrough(CarbonImmutable $date): void
    {
        $trackingStart = DB::table('statistics_tracking')->value('started_on');
        $cursor = is_string($trackingStart)
            ? $this->dates->fromDateString($trackingStart)->startOfMonth()
            : $date->startOfMonth();
        $lastMonth = $date->startOfMonth();

        $materialized = DB::table('monthly_materializations')
            ->whereDate('month_start', '>=', $cursor->toDateString())
            ->whereDate('month_start', '<=', $lastMonth->toDateString())
            ->pluck('month_start')
            ->mapWithKeys(static fn ($value): array => [substr((string) $value, 0, 10) => true]);

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            if (! $materialized->has($cursor->toDateString())) {
                $this->materializeMonth($cursor);
            }
            $cursor = $cursor->addMonth();
        }

        $dateString = $date->toDateString();

        MonthlyTaskOccurrence::query()
            ->where('status', 'pending')
            ->whereDate('month_start', '<=', $dateString)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id) use ($date): void {
                DB::transaction(function () use ($id, $date): void {
                    $occurrence = MonthlyTaskOccurrence::query()->lockForUpdate()->findOrFail($id);

                    while ($occurrence->status === 'pending' && $occurrence->scheduled_date->lessThan($date)) {
                        $this->postponeOrMiss($occurrence);
                        $occurrence->refresh();
                    }

                    if (
                        $occurrence->status === 'pending'
                        && $occurrence->scheduled_date->isSameDay($date)
                    ) {
                        if ($this->calendar->isPublicHoliday($date)) {
                            $this->postponeOrMiss($occurrence, 'public_holiday');
                        } elseif ($this->isUnavailable($date)) {
                            $this->postponeOrMiss($occurrence, 'unavailable');
                        }
                    }
                }, 3);
            });
    }

    /**
     * @return Collection<int, MonthlyTaskOccurrence>
     */
    public function forChecklistDate(CarbonImmutable $date): Collection
    {
        $today = $this->dates->today();

        if ($this->calendar->isPublicHoliday($date)
            || (! $this->dates->isWorkingDay($date) && $date->greaterThanOrEqualTo($today))) {
            return new Collection;
        }

        $this->materializeMonth($date);
        $this->advanceThrough($date->lessThan($today) ? $date : $today);
        $monthStart = $date->startOfMonth()->toDateString();
        $query = MonthlyTaskOccurrence::query()
            ->withCount(['evidence', 'postponements']);

        if ($date->isSameDay($today)) {
            $query->where(function ($builder) use ($date, $monthStart): void {
                $builder->where(function ($currentMonth) use ($monthStart, $date): void {
                    $currentMonth->whereDate('month_start', $monthStart)
                        ->where('status', 'pending')
                        ->whereDate('scheduled_date', '<=', $date->toDateString());
                })->orWhereDate('completed_on', $date->toDateString())
                    ->orWhere(function ($carried) use ($date, $monthStart): void {
                        $carried->where('status', 'pending')
                            ->whereDate('scheduled_date', $date->toDateString())
                            ->whereDate('month_start', '!=', $monthStart);
                    });
            });
        } else {
            $query->where(function ($builder) use ($date): void {
                $builder->whereDate('completed_on', $date->toDateString())
                    ->orWhereHas('postponements', function ($postponements) use ($date): void {
                        $postponements->whereDate('from_date', $date->toDateString());
                    })
                    ->orWhere(function ($nested) use ($date): void {
                        $nested->whereIn('status', ['pending', 'missed'])
                            ->whereDate('scheduled_date', $date->toDateString());
                    });
            });
        }

        return $query->orderBy('id')->get();
    }

    public function updateTemplateSnapshots(MonthlyTaskTemplate $template, ?CarbonImmutable $fromMonth = null): bool
    {
        $start = ($fromMonth ?? $template->starts_on)->startOfMonth();
        $todayMonth = $this->dates->today()->startOfMonth();

        if ($start->lessThan($todayMonth)) {
            $start = $todayMonth;
        }

        $this->refreshMaterializedMonthsFrom($start);

        return true;
    }

    public function refreshMaterializedMonthsFrom(CarbonImmutable $date): void
    {
        DB::table('monthly_materializations')
            ->whereDate('month_start', '>=', $date->startOfMonth()->toDateString())
            ->orderBy('month_start')
            ->pluck('month_start')
            ->each(function ($monthStart): void {
                $this->materializeMonth($this->dates->fromDateString(substr((string) $monthStart, 0, 10)), true);
            });
    }

    public function reconcilePublicHolidaySchedulesFrom(CarbonImmutable $from): void
    {
        DB::transaction(function () use ($from): void {
            $this->materializer->acquireTemplateSynchronizationLock();
            $occurrences = MonthlyTaskOccurrence::query()
                ->where('status', 'pending')
                ->whereDate('original_due_date', '>=', $from->toDateString())
                ->orderBy('original_due_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $occurrences->each(function (MonthlyTaskOccurrence $occurrence): void {
                $occurrence->postponements()
                    ->where('reason', 'public_holiday')
                    ->delete();
                $occurrence->forceFill([
                    'scheduled_date' => $occurrence->original_due_date->toDateString(),
                    'missed_reason' => null,
                ])->save();
            });

            $occurrences->each(function (MonthlyTaskOccurrence $occurrence): void {
                if ($this->calendar->isPublicHoliday($occurrence->scheduled_date)) {
                    $this->postponeOrMiss($occurrence, 'public_holiday');
                }
            });
        }, 3);
    }

    public function deactivateTemplate(MonthlyTaskTemplate $template): void
    {
        DB::transaction(function () use ($template): void {
            $this->materializer->acquireTemplateSynchronizationLock();
            $template->forceFill(['is_active' => false])->save();
            $occurrenceIds = $template->occurrences()
                ->where('status', 'pending')
                ->whereDate('month_start', '>=', $this->dates->today()->startOfMonth()->toDateString())
                ->pluck('id');
            $template->occurrences()
                ->whereKey($occurrenceIds)
                ->delete();
        }, 3);
    }

    private function postponeOrMiss(MonthlyTaskOccurrence $occurrence, ?string $forcedReason = null): void
    {
        $from = $occurrence->scheduled_date;
        $reason = $forcedReason ?? ($this->calendar->isPublicHoliday($from) ? 'public_holiday' : ($this->isUnavailable($from) ? 'unavailable' : 'incomplete'));

        // For monthly tasks, shift backwards to the preceding working day in the same month
        $prevWorkingDay = $this->precedingWorkingDayInMonth($from);

        if ($prevWorkingDay === null) {
            $occurrence->forceFill([
                'status' => 'missed',
                'missed_reason' => $reason,
            ])->save();

            return;
        }

        MonthlyTaskPostponement::query()->firstOrCreate(
            ['monthly_task_occurrence_id' => $occurrence->id, 'from_date' => $from->toDateString()],
            [
                'to_date' => $prevWorkingDay->toDateString(),
                'reason' => $reason,
            ],
        );
        $occurrence->forceFill(['scheduled_date' => $prevWorkingDay->toDateString()])->save();
    }

    private function precedingWorkingDayInMonth(CarbonImmutable $date): ?CarbonImmutable
    {
        $cursor = $date->subDay();
        $month = $date->month;

        while ($cursor->month === $month) {
            if ($this->calendar->isWorkingDay($cursor)
                && ! $this->calendar->isPublicHoliday($cursor)
                && ! $this->isUnavailable($cursor)) {
                return $cursor;
            }
            $cursor = $cursor->subDay();
        }

        return null;
    }

    private function isUnavailable(CarbonImmutable $date): bool
    {
        return ChecklistDayStatus::query()
            ->whereDate('date', $date->toDateString())
            ->where('is_unavailable', true)
            ->exists();
    }

    private function syncTemplateOccurrence(MonthlyTaskTemplate $template, CarbonImmutable $monthStart): void
    {
        $template->loadMissing('taskSession:id,name');
        $lastFriday = $this->lastFridayOfMonth($monthStart);
        $existing = MonthlyTaskOccurrence::query()
            ->whereDate('month_start', $monthStart->toDateString())
            ->where('monthly_task_template_id', $template->id)
            ->first();

        // Calculate initial scheduled date (shifting backwards if last Friday is holiday or unavailable)
        $scheduledDate = $lastFriday;
        $missedReason = null;
        $status = 'pending';

        if ($this->calendar->isPublicHoliday($scheduledDate) || $this->isUnavailable($scheduledDate) || ! $this->calendar->isWorkingDay($scheduledDate)) {
            $prev = $this->precedingWorkingDayInMonth($scheduledDate);
            if ($prev !== null) {
                $scheduledDate = $prev;
            } else {
                $status = 'missed';
                $missedReason = 'public_holiday';
            }
        }

        if ($existing === null) {
            $created = MonthlyTaskOccurrence::query()->create([
                'month_start' => $monthStart->toDateString(),
                'monthly_task_template_id' => $template->id,
                'task_session_id' => $template->task_session_id,
                'task_name' => $template->task_name,
                'description' => $template->description,
                'session_name' => $template->taskSession->name,
                'finish_time' => $template->finish_time,
                'original_due_date' => $lastFriday->toDateString(),
                'scheduled_date' => $scheduledDate->toDateString(),
                'status' => $status,
                'missed_reason' => $missedReason,
            ]);

            if (! $scheduledDate->isSameDay($lastFriday)) {
                MonthlyTaskPostponement::query()->create([
                    'monthly_task_occurrence_id' => $created->id,
                    'from_date' => $lastFriday->toDateString(),
                    'to_date' => $scheduledDate->toDateString(),
                    'reason' => 'public_holiday',
                ]);
            }

            return;
        }

        if ($existing->status !== 'pending') {
            return;
        }

        $changes = [
            'task_name' => $template->task_name,
            'description' => $template->description,
            'task_session_id' => $template->task_session_id,
            'session_name' => $template->taskSession->name,
            'finish_time' => $template->finish_time,
            'original_due_date' => $lastFriday->toDateString(),
        ];

        $existing->forceFill($changes)->save();
    }
}
