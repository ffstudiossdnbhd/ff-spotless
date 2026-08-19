<?php

namespace App\Services;

use App\Models\DailyChecklist;
use App\Models\MonthlyTaskOccurrence;
use App\Models\WeeklyTaskOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    public function __construct(
        private readonly ChecklistMaterializer $daily,
        private readonly WeeklyTaskScheduler $weekly,
        private readonly MonthlyTaskScheduler $monthly,
        private readonly OperationalDate $dates,
        private readonly OfficeCalendar $calendar,
    ) {}

    public function trackingStart(): string
    {
        return (string) DB::table('statistics_tracking')->value('started_on');
    }

    public function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $this->daily->catchUpThrough($this->dates->today());
        $this->weekly->advanceThrough($this->dates->today());
        $this->monthly->advanceThrough($this->dates->today());

        $today = $this->dates->today();
        $queryFrom = $from;
        $queryTo = $to;

        $dailyTasks = DailyChecklist::query()
            ->whereDate('date', '>=', $queryFrom->toDateString())
            ->whereDate('date', '<=', $queryTo->toDateString())
            ->get();

        $weekly = WeeklyTaskOccurrence::query()
            ->where(function ($query) use ($queryFrom, $queryTo): void {
                $query->where(function ($scheduled) use ($queryFrom, $queryTo): void {
                    $scheduled->whereDate('scheduled_date', '>=', $queryFrom->toDateString())
                        ->whereDate('scheduled_date', '<=', $queryTo->toDateString());
                })->orWhere(function ($completed) use ($queryFrom, $queryTo): void {
                    $completed->whereDate('completed_on', '>=', $queryFrom->toDateString())
                        ->whereDate('completed_on', '<=', $queryTo->toDateString());
                });
            })
            ->get();

        $monthly = MonthlyTaskOccurrence::query()
            ->where(function ($query) use ($queryFrom, $queryTo): void {
                $query->where(function ($scheduled) use ($queryFrom, $queryTo): void {
                    $scheduled->whereDate('scheduled_date', '>=', $queryFrom->toDateString())
                        ->whereDate('scheduled_date', '<=', $queryTo->toDateString());
                })->orWhere(function ($completed) use ($queryFrom, $queryTo): void {
                    $completed->whereDate('completed_on', '>=', $queryFrom->toDateString())
                        ->whereDate('completed_on', '<=', $queryTo->toDateString());
                });
            })
            ->get();

        $dailyByDate = $dailyTasks->groupBy(static fn (DailyChecklist $task): string => $task->date->toDateString());
        $weeklyByScheduledDate = $weekly->groupBy(static fn (WeeklyTaskOccurrence $task): string => $task->scheduled_date->toDateString());
        $weeklyByCompletedDate = $weekly
            ->filter(static fn (WeeklyTaskOccurrence $task): bool => $task->completed_on !== null)
            ->groupBy(static fn (WeeklyTaskOccurrence $task): string => $task->completed_on->toDateString());

        $monthlyByScheduledDate = $monthly->groupBy(static fn (MonthlyTaskOccurrence $task): string => $task->scheduled_date->toDateString());
        $monthlyByCompletedDate = $monthly
            ->filter(static fn (MonthlyTaskOccurrence $task): bool => $task->completed_on !== null)
            ->groupBy(static fn (MonthlyTaskOccurrence $task): string => $task->completed_on->toDateString());

        $rowForDate = function (CarbonImmutable $cursor) use ($dailyByDate, $weeklyByScheduledDate, $weeklyByCompletedDate, $monthlyByScheduledDate, $monthlyByCompletedDate, $today): array {
            $date = $cursor->toDateString();
            $row = ['date' => $date, 'completed' => 0, 'missed' => 0, 'pending' => 0];

            foreach ($dailyByDate->get($date, []) as $task) {
                if ($task->is_completed) {
                    $row['completed']++;
                } elseif ($cursor->lessThan($today)) {
                    $row['missed']++;
                } else {
                    $row['pending']++;
                }
            }

            foreach ($weeklyByScheduledDate->get($date, []) as $task) {
                if ($task->status === 'missed') {
                    $row['missed']++;
                } elseif ($task->status === 'pending') {
                    $row['pending']++;
                }
            }

            foreach ($weeklyByCompletedDate->get($date, []) as $task) {
                if ($task->status === 'completed') {
                    $row['completed']++;
                }
            }

            foreach ($monthlyByScheduledDate->get($date, []) as $task) {
                if ($task->status === 'missed') {
                    $row['missed']++;
                } elseif ($task->status === 'pending') {
                    $row['pending']++;
                }
            }

            foreach ($monthlyByCompletedDate->get($date, []) as $task) {
                if ($task->status === 'completed') {
                    $row['completed']++;
                }
            }

            return $row;
        };

        $overview = ['completed' => 0, 'missed' => 0, 'pending' => 0];

        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            if ($this->calendar->isPublicHoliday($cursor)) {
                continue;
            }

            $row = $rowForDate($cursor);
            $overview['completed'] += $row['completed'];
            $overview['missed'] += $row['missed'];
            $overview['pending'] += $row['pending'];
        }

        $closed = $overview['completed'] + $overview['missed'];
        $overview['completionRate'] = $closed > 0 ? round(($overview['completed'] / $closed) * 100) : 0;
        $overview['totalTasks'] = $overview['completed'] + $overview['missed'] + $overview['pending'];

        $trend = [];
        for ($cursor = $from; $cursor->lessThanOrEqualTo($to); $cursor = $cursor->addDay()) {
            if ($this->calendar->isWorkingDay($cursor)) {
                $trend[] = $rowForDate($cursor);
            }
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'trackingStart' => $this->trackingStart(),
            'overview' => $overview,
            'trend' => $trend,
        ];
    }
}
