<?php

namespace App\Services;

use App\Models\ChecklistDayStatus;
use App\Models\DailyChecklist;
use App\Models\AuditLog;
use App\Models\MonthlyTaskOccurrence;
use App\Models\MonthlyTaskTemplate;
use App\Models\PublicHoliday;
use App\Models\TaskCollection;
use App\Models\TaskCollectionSchedule;
use App\Models\TaskSession;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\WeeklyTaskOccurrence;
use App\Models\WeeklyTaskTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class DashboardPresenter
{
    public function __construct(
        private readonly MasterAdminSession $adminSession,
        private readonly OperationalDate $dates,
        private readonly OfficeCalendar $calendar,
        private readonly TaskCollectionResolver $collections,
    ) {}

    public function welcome(Request $request): array
    {
        return $this->base($request, 'welcome', $this->dates->today(), []);
    }

    /**
     * @param  array{daily: Collection<int, DailyChecklist>, weekly: Collection<int, WeeklyTaskOccurrence>, monthly?: Collection<int, MonthlyTaskOccurrence>}  $checklist
     */
    public function checklist(Request $request, CarbonImmutable $date, array $checklist): array
    {
        return $this->base($request, 'checklist', $date, $this->taskItems($date, $checklist));
    }

    /**
     * @param  Collection<int, TaskTemplate>  $templates
     * @param  Collection<int, WeeklyTaskTemplate>  $weeklyTemplates
     * @param  Collection<int, MonthlyTaskTemplate>  $monthlyTemplates
     * @param  Collection<int, PublicHoliday>  $publicHolidays
     * @param  array{daily: Collection<int, DailyChecklist>, weekly: Collection<int, WeeklyTaskOccurrence>, monthly?: Collection<int, MonthlyTaskOccurrence>}  $checklist
     */
    public function admin(
        Request $request,
        CarbonImmutable $date,
        Collection $templates,
        Collection $weeklyTemplates,
        Collection $monthlyTemplates,
        Collection $collections,
        Collection $collectionSchedules,
        Collection $publicHolidays,
        array $checklist,
        array $statistics,
        CarbonImmutable $rotationCalendarMonth,
    ): array {
        $props = $this->base($request, 'admin', $date, []);
        $sessions = TaskSession::query()->orderBy('start_time')->get()->keyBy('id');

        $props['templates'] = $this->enrichTemplatesWithTimes($templates, $sessions, 'daily');
        $props['weeklyTemplates'] = $this->enrichTemplatesWithTimes($weeklyTemplates, $sessions, 'weekly');
        $props['monthlyTemplates'] = $this->enrichTemplatesWithTimes($monthlyTemplates, $sessions, 'monthly');

        $props['collections'] = $collections->map(static fn (TaskCollection $collection): array => [
            'id' => $collection->id,
            'name' => $collection->name,
            'isDefault' => $collection->is_default,
            'rotationOrder' => $collection->rotation_order,
        ])->values()->all();

        $props['collectionSchedules'] = $collectionSchedules->map(static fn (TaskCollectionSchedule $schedule): array => [
            'id' => $schedule->id,
            'collectionId' => $schedule->task_collection_id,
            'collectionName' => $schedule->taskCollection?->name ?? 'General',
            'startsOn' => $schedule->starts_on->toDateString(),
            'endsOn' => $schedule->ends_on->toDateString(),
        ])->values()->all();

        $props['publicHolidays'] = $publicHolidays->map(function (PublicHoliday $holiday): array {
            return [
                'id' => $holiday->id,
                'date' => $holiday->date->toDateString(),
                'name' => $holiday->name,
                'isEditable' => $holiday->date->toDateString() > $this->dates->today()->toDateString(),
            ];
        })->values()->all();

        $selectedHoliday = $publicHolidays->first(
            static fn (PublicHoliday $holiday): bool => $holiday->date->isSameDay($date),
        );
        $props['publicHoliday'] = $this->publicHolidayPayload($selectedHoliday instanceof PublicHoliday ? $selectedHoliday : null);
        $props['completedTasks'] = $this->historyItems($date, $checklist);
        $props['rotationCalendar'] = $this->rotationCalendar($rotationCalendarMonth);
        $props['auditLogs'] = $this->auditLogs();
        $props['statistics'] = $statistics;

        return $props;
    }

    /**
     * @param  array{daily: Collection<int, DailyChecklist>, weekly: Collection<int, WeeklyTaskOccurrence>, monthly?: Collection<int, MonthlyTaskOccurrence>}  $checklist
     * @return list<array<string, mixed>>
     */
    private function taskItems(CarbonImmutable $date, array $checklist): array
    {
        $sessions = TaskSession::query()->orderBy('start_time')->get()->keyBy('id');

        $daily = $checklist['daily']->map(function (DailyChecklist $task): array {
            return [
                'key' => 'daily:'.$task->id,
                'type' => 'daily',
                'id' => $task->id,
                'text' => $task->task_name,
                'description' => $task->description,
                'sessionId' => $task->task_session_id,
                'sessionName' => $task->session_name,
                'finishTime' => $this->formatTime($task->finish_time),
                'completed' => $task->is_completed,
                'isWeekly' => false,
                'isMonthly' => false,
                'evidenceCount' => $task->evidence_count ?? 0,
            ];
        });

        $weekly = $checklist['weekly']->map(function (WeeklyTaskOccurrence $task): array {
            return [
                'key' => 'weekly:'.$task->id,
                'type' => 'weekly',
                'id' => $task->id,
                'text' => $task->task_name,
                'description' => $task->description,
                'sessionId' => $task->task_session_id,
                'sessionName' => $task->session_name,
                'finishTime' => $this->formatTime($task->finish_time),
                'completed' => $task->status === 'completed',
                'isWeekly' => true,
                'isMonthly' => false,
                'status' => $task->status,
                'originalDueDate' => $task->original_due_date->toDateString(),
                'scheduledDate' => $task->scheduled_date->toDateString(),
                'postponedCount' => $task->postponements_count ?? 0,
                'evidenceCount' => $task->evidence_count ?? 0,
            ];
        });

        $monthly = ($checklist['monthly'] ?? collect())->map(function (MonthlyTaskOccurrence $task): array {
            return [
                'key' => 'monthly:'.$task->id,
                'type' => 'monthly',
                'id' => $task->id,
                'text' => $task->task_name,
                'description' => $task->description,
                'sessionId' => $task->task_session_id,
                'sessionName' => $task->session_name,
                'finishTime' => $this->formatTime($task->finish_time),
                'completed' => $task->status === 'completed',
                'isWeekly' => false,
                'isMonthly' => true,
                'status' => $task->status,
                'originalDueDate' => $task->original_due_date->toDateString(),
                'scheduledDate' => $task->scheduled_date->toDateString(),
                'postponedCount' => $task->postponements_count ?? 0,
                'evidenceCount' => $task->evidence_count ?? 0,
            ];
        });

        $allTasks = $daily->concat($weekly)->concat($monthly);

        // Group by session and compute dynamic start times
        $result = [];
        $grouped = $allTasks->groupBy('sessionId');

        foreach ($sessions as $sessionId => $session) {
            $sessionTasks = $grouped->get($sessionId, collect())
                ->sortBy(static fn (array $item): string => $item['finishTime'].':'.$item['id'])
                ->values();

            $prevFinish = $this->formatTime($session->start_time);

            foreach ($sessionTasks as $task) {
                $startTime = $prevFinish;
                $finishTime = $task['finishTime'];
                $startTimeFormatted = $this->formatHumanTime($startTime);
                $finishTimeFormatted = $this->formatHumanTime($finishTime);

                $task['startTime'] = $startTime;
                $task['startTimeFormatted'] = $startTimeFormatted;
                $task['finishTimeFormatted'] = $finishTimeFormatted;
                $task['timeSpan'] = "{$startTimeFormatted} - {$finishTimeFormatted}";

                $prevFinish = $finishTime;
                $result[] = $task;
            }
        }

        return $result;
    }

    private function enrichTemplatesWithTimes(Collection $templates, \Illuminate\Support\Collection $sessions, string $type): array
    {
        $grouped = $templates->groupBy('task_session_id');
        $result = [];

        foreach ($sessions as $sessionId => $session) {
            $sessionTemplates = $grouped->get($sessionId, collect())
                ->sortBy(static fn ($template): string => (string) $template->finish_time.':'.$template->id)
                ->values();

            $prevFinish = $this->formatTime($session->start_time);

            foreach ($sessionTemplates as $template) {
                $finishTime = $this->formatTime($template->finish_time);
                $startTime = $prevFinish;
                $startTimeFormatted = $this->formatHumanTime($startTime);
                $finishTimeFormatted = $this->formatHumanTime($finishTime);

                $item = [
                    'id' => $template->id,
                    'taskName' => $template->task_name,
                    'description' => $template->description,
                    'type' => $type,
                    'sessionId' => $template->task_session_id,
                    'sessionName' => $template->taskSession?->name ?? $session->name,
                    'startTime' => $startTime,
                    'finishTime' => $finishTime,
                    'startTimeFormatted' => $startTimeFormatted,
                    'finishTimeFormatted' => $finishTimeFormatted,
                    'timeSpan' => "{$startTimeFormatted} - {$finishTimeFormatted}",
                    'appliesToAllCollections' => $template->applies_to_all_collections,
                    'collectionIds' => $template->taskCollections->pluck('id')->values()->all(),
                    'collectionNames' => $template->taskCollections->pluck('name')->values()->all(),
                ];

                if ($type === 'weekly') {
                    $item['dueWeekday'] = $template->due_weekday;
                    $item['startsOn'] = $template->starts_on->toDateString();
                } elseif ($type === 'monthly') {
                    $item['startsOn'] = $template->starts_on->toDateString();
                }

                $prevFinish = $finishTime;
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param  array{daily: Collection<int, DailyChecklist>, weekly: Collection<int, WeeklyTaskOccurrence>, monthly?: Collection<int, MonthlyTaskOccurrence>}  $checklist
     * @return list<array<string, mixed>>
     */
    private function historyItems(CarbonImmutable $date, array $checklist): array
    {
        $daily = $checklist['daily']->loadMissing(['evidence', 'completedBy:id,name,username'])
            ->map(function (DailyChecklist $task): array {
                return [
                    'key' => 'daily:'.$task->id,
                    'type' => 'daily',
                    'id' => $task->id,
                    'date' => $task->date->toDateString(),
                    'text' => $task->task_name,
                    'description' => $task->description,
                    'sessionId' => $task->task_session_id,
                    'sessionName' => $task->session_name,
                    'finishTime' => $this->formatTime($task->finish_time),
                    'status' => $task->is_completed ? 'completed' : ($task->date->lessThan($this->dates->today()) ? 'missed' : 'pending'),
                    'isCompleted' => $task->is_completed,
                    'completedAt' => $this->localTimestamp($task->completed_at),
                    'completionNote' => $task->completion_note,
                    'completedBy' => $task->completedBy?->only(['id', 'name', 'username']),
                    'canReopen' => $task->is_completed && $task->date->isSameDay($this->dates->today()),
                    'evidence' => $task->evidence->map(static fn ($evidence): array => [
                        'id' => $evidence->id,
                        'url' => route('admin.evidence.daily', $evidence),
                    ])->values()->all(),
                ];
            });

        $weekly = $checklist['weekly']->loadMissing(['evidence', 'completedBy:id,name,username', 'postponements'])
            ->map(function (WeeklyTaskOccurrence $task) use ($date): array {
                return [
                    'key' => 'weekly:'.$task->id,
                    'type' => 'weekly',
                    'id' => $task->id,
                    'date' => $date->toDateString(),
                    'text' => $task->task_name,
                    'description' => $task->description,
                    'sessionId' => $task->task_session_id,
                    'sessionName' => $task->session_name,
                    'finishTime' => $this->formatTime($task->finish_time),
                    'status' => $task->status,
                    'missedReason' => $task->missed_reason,
                    'isCompleted' => $task->status === 'completed',
                    'completedAt' => $this->localTimestamp($task->completed_at),
                    'completionNote' => $task->completion_note,
                    'completedBy' => $task->completedBy?->only(['id', 'name', 'username']),
                    'originalDueDate' => $task->original_due_date->toDateString(),
                    'scheduledDate' => $task->scheduled_date->toDateString(),
                    'canReopen' => $task->status === 'completed'
                        && $task->week_start->isSameDay($this->dates->today()->startOfWeek()),
                    'postponements' => $task->postponements->map(static fn ($postponement): array => [
                        'from' => $postponement->from_date->toDateString(),
                        'to' => $postponement->to_date->toDateString(),
                        'reason' => $postponement->reason,
                    ])->values()->all(),
                    'evidence' => $task->evidence->map(static fn ($evidence): array => [
                        'id' => $evidence->id,
                        'url' => route('admin.evidence.weekly', $evidence),
                    ])->values()->all(),
                ];
            });

        $monthly = ($checklist['monthly'] ?? collect())->loadMissing(['evidence', 'completedBy:id,name,username', 'postponements'])
            ->map(function (MonthlyTaskOccurrence $task) use ($date): array {
                return [
                    'key' => 'monthly:'.$task->id,
                    'type' => 'monthly',
                    'id' => $task->id,
                    'date' => $date->toDateString(),
                    'text' => $task->task_name,
                    'description' => $task->description,
                    'sessionId' => $task->task_session_id,
                    'sessionName' => $task->session_name,
                    'finishTime' => $this->formatTime($task->finish_time),
                    'status' => $task->status,
                    'missedReason' => $task->missed_reason,
                    'isCompleted' => $task->status === 'completed',
                    'completedAt' => $this->localTimestamp($task->completed_at),
                    'completionNote' => $task->completion_note,
                    'completedBy' => $task->completedBy?->only(['id', 'name', 'username']),
                    'originalDueDate' => $task->original_due_date->toDateString(),
                    'scheduledDate' => $task->scheduled_date->toDateString(),
                    'canReopen' => $task->status === 'completed'
                        && $task->month_start->isSameMonth($this->dates->today()),
                    'postponements' => $task->postponements->map(static fn ($postponement): array => [
                        'from' => $postponement->from_date->toDateString(),
                        'to' => $postponement->to_date->toDateString(),
                        'reason' => $postponement->reason,
                    ])->values()->all(),
                    'evidence' => $task->evidence->map(static fn ($evidence): array => [
                        'id' => $evidence->id,
                        'url' => route('admin.evidence.monthly', $evidence),
                    ])->values()->all(),
                ];
            });

        $allHistory = $daily->concat($weekly)->concat($monthly);
        $sessions = TaskSession::query()->orderBy('start_time')->get()->keyBy('id');
        $result = [];
        $grouped = $allHistory->groupBy('sessionId');

        foreach ($sessions as $sessionId => $session) {
            $sessionTasks = $grouped->get($sessionId, collect())
                ->sortBy(static fn (array $item): string => $item['finishTime'].':'.$item['id'])
                ->values();

            $prevFinish = $this->formatTime($session->start_time);

            foreach ($sessionTasks as $task) {
                $startTime = $prevFinish;
                $finishTime = $task['finishTime'];
                $startTimeFormatted = $this->formatHumanTime($startTime);
                $finishTimeFormatted = $this->formatHumanTime($finishTime);

                $task['startTime'] = $startTime;
                $task['startTimeFormatted'] = $startTimeFormatted;
                $task['finishTimeFormatted'] = $finishTimeFormatted;
                $task['timeSpanFormatted'] = "{$startTimeFormatted} - {$finishTimeFormatted}";

                $prevFinish = $finishTime;
                $result[] = $task;
            }
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function auditLogs(): LengthAwarePaginator
    {
        return AuditLog::query()
            ->orderByDesc('occurred_at')
            ->paginate(50, ['*'], 'audit_page')
            ->withQueryString()
            ->through(fn (AuditLog $audit): array => [
                'id' => $audit->id,
                'action' => $audit->action,
                'actorType' => $audit->actor_type,
                'actorLabel' => $audit->actor_label,
                'subjectType' => $audit->subject_type,
                'subjectId' => $audit->subject_id,
                'metadata' => $audit->metadata ?? [],
                'occurredAt' => $this->localTimestamp($audit->occurred_at),
            ]);
    }

    private function base(Request $request, string $mode, CarbonImmutable $date, array $tasks): array
    {
        $user = $request->user();
        $sessions = Schema::hasTable('task_sessions')
            ? TaskSession::query()->orderBy('start_time')->get()
            : collect();
        $dateString = $date->toDateString();

        return [
            'mode' => $mode,
            'auth' => [
                'user' => $user instanceof User ? $user->only(['id', 'name', 'username']) : null,
                'isAdmin' => $this->adminSession->isAuthenticated($request),
            ],
            'sessions' => $sessions->map(fn (TaskSession $session): array => [
                'id' => $session->id,
                'name' => $session->name,
                'startTime' => $this->formatTime($session->start_time),
                'endTime' => $this->formatTime($session->end_time),
                'startTimeFormatted' => $this->formatHumanTime($session->start_time),
                'endTimeFormatted' => $this->formatHumanTime($session->end_time),
                'sortOrder' => $session->sort_order,
                'isActive' => $session->is_active,
            ])->values()->all(),
            'tasks' => $tasks,
            'currentDate' => $dateString,
            'isReadOnly' => ! $this->dates->isToday($dateString),
            'dayUnavailable' => Schema::hasTable('checklist_day_statuses')
                && ChecklistDayStatus::query()
                    ->whereDate('date', $dateString)
                    ->where('is_unavailable', true)
                    ->exists(),
            'publicHoliday' => $this->selectedPublicHoliday($date),
            'uploadLimits' => $this->uploadLimits(),
            'templates' => [],
            'weeklyTemplates' => [],
            'monthlyTemplates' => [],
            'collections' => [],
            'collectionSchedules' => [],
            'publicHolidays' => [],
            'completedTasks' => [],
            'rotationCalendar' => ['month' => null, 'weeks' => []],
            'auditLogs' => ['data' => [], 'links' => []],
            'statistics' => null,
        ];
    }

    /**
     * @return array{id: int, date: string, name: string}|null
     */
    private function selectedPublicHoliday(CarbonImmutable $date): ?array
    {
        if (! Schema::hasTable('public_holidays')) {
            return null;
        }

        $holiday = $this->calendar->publicHoliday($date);

        return $this->publicHolidayPayload($holiday);
    }

    /**
     * @return array{id: int, date: string, name: string}|null
     */
    private function publicHolidayPayload(?PublicHoliday $holiday): ?array
    {
        if ($holiday === null) {
            return null;
        }

        return [
            'id' => $holiday->id,
            'date' => $holiday->date->toDateString(),
            'name' => $holiday->name,
        ];
    }

    /**
     * @return array{month: string, weeks: list<array<string, mixed>>}
     */
    private function rotationCalendar(CarbonImmutable $month): array
    {
        $calendarMonth = $month->startOfMonth();
        $gridStart = $calendarMonth->startOfWeek(CarbonInterface::SUNDAY);
        $gridEnd = $calendarMonth->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY);
        $weeks = [];

        for ($weekStart = $gridStart; $weekStart->lessThanOrEqualTo($gridEnd); $weekStart = $weekStart->addWeek()) {
            $rotation = $this->collections->forDate($weekStart);
            $weeks[] = [
                'weekStart' => $weekStart->toDateString(),
                'calendarWeek' => $this->sundayWeekOfYear($weekStart, $calendarMonth->year),
                'rotation' => [
                    'id' => $rotation->id,
                    'name' => $rotation->name,
                    'isDefault' => $rotation->is_default,
                ],
                'days' => collect(range(0, 6))->map(function (int $offset) use ($weekStart, $calendarMonth): array {
                    $day = $weekStart->addDays($offset);

                    return [
                        'date' => $day->toDateString(),
                        'dayNumber' => $day->day,
                        'inMonth' => $day->isSameMonth($calendarMonth),
                        'isToday' => $day->isSameDay($this->dates->today()),
                        'isWeekend' => in_array($day->dayOfWeek, [CarbonInterface::SUNDAY, CarbonInterface::SATURDAY], true),
                    ];
                })->all(),
            ];
        }

        return [
            'month' => $calendarMonth->format('Y-m'),
            'weeks' => $weeks,
        ];
    }

    private function sundayWeekOfYear(CarbonImmutable $weekStart, int $year): int
    {
        $firstWeekStart = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $this->dates->timezone())
            ->startOfWeek(CarbonInterface::SUNDAY);

        return (int) floor($firstWeekStart->diffInDays($weekStart, false) / 7) + 1;
    }

    private function localTimestamp($timestamp): ?string
    {
        return $timestamp?->setTimezone($this->dates->timezone())->format('Y-m-d\\TH:i:s.uP');
    }

    private function formatTime(?string $time): string
    {
        if (! $time) {
            return '09:00:00';
        }

        return strlen($time) === 5 ? "{$time}:00" : substr($time, 0, 8);
    }

    private function formatHumanTime(?string $time): string
    {
        if (! $time) {
            return '9:00 AM';
        }

        try {
            return CarbonImmutable::parse($this->formatTime($time))->format('g:i A');
        } catch (\Throwable) {
            return $time;
        }
    }

    /**
     * @return array{maxFiles: int, maxFileMb: int|float, maxFileBytes: int, maxRequestMb: int|float, maxRequestBytes: int}
     */
    private function uploadLimits(): array
    {
        $configuredFiles = max(1, (int) config('checklist.evidence.max_files', 5));
        $configuredFileBytes = max(1, (int) config('checklist.evidence.max_file_kb', 10240)) * 1024;
        $configuredRequestBytes = max(1, (int) config('checklist.evidence.max_request_kb', 56320)) * 1024;
        $phpFiles = (int) ini_get('max_file_uploads');
        $phpFileBytes = $this->phpSizeToBytes((string) ini_get('upload_max_filesize'));
        $phpRequestBytes = $this->phpSizeToBytes((string) ini_get('post_max_size'));
        $maxFiles = $phpFiles > 0 ? min($configuredFiles, $phpFiles) : $configuredFiles;
        $maxFileBytes = $phpFileBytes > 0 ? min($configuredFileBytes, $phpFileBytes) : $configuredFileBytes;
        $maxRequestBytes = $phpRequestBytes > 0 ? min($configuredRequestBytes, $phpRequestBytes) : $configuredRequestBytes;

        return [
            'maxFiles' => $maxFiles,
            'maxFileMb' => $this->megabytes($maxFileBytes),
            'maxFileBytes' => $maxFileBytes,
            'maxRequestMb' => $this->megabytes($maxRequestBytes),
            'maxRequestBytes' => $maxRequestBytes,
        ];
    }

    private function phpSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $suffix = strtolower(substr($value, -1));
        $multiplier = match ($suffix) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1,
        };

        return (int) round($number * $multiplier);
    }

    private function megabytes(int $bytes): int|float
    {
        $megabytes = round($bytes / 1024 / 1024, 1);

        return $megabytes === floor($megabytes) ? (int) $megabytes : $megabytes;
    }
}
