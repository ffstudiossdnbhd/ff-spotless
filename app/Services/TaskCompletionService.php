<?php

namespace App\Services;

use App\Models\ChecklistDayStatus;
use App\Models\DailyChecklist;
use App\Models\DailyTaskEvidence;
use App\Models\MonthlyTaskEvidence;
use App\Models\MonthlyTaskOccurrence;
use App\Models\WeeklyTaskEvidence;
use App\Models\WeeklyTaskOccurrence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class TaskCompletionService
{
    public function __construct(
        private readonly OperationalDate $dates,
        private readonly OfficeCalendar $calendar,
        private readonly WeeklyTaskScheduler $weekly,
        private readonly MonthlyTaskScheduler $monthly,
        private readonly ChecklistMaterializer $materializer,
        private readonly EvidenceWatermarker $watermarker,
        private readonly AuditLogger $audits,
        private readonly WebPushService $webPush,
    ) {}

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function completeDaily(DailyChecklist $task, string $date, array $photos, ?string $note = null): void
    {
        $this->assertWritableDate($date);
        $note = $this->normaliseNote($note);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($task, $date, $photos, $note, &$storedPaths): void {
                $this->materializer->acquireTemplateSynchronizationLock();
                $this->assertAvailableDate($date);
                $locked = DailyChecklist::query()->lockForUpdate()->findOrFail($task->id);

                if (! hash_equals($locked->date->toDateString(), $date)) {
                    throw ValidationException::withMessages(['date' => 'Tugasan tidak sepadan dengan tarikh ini.']);
                }

                if ($locked->is_completed) {
                    throw ValidationException::withMessages(['task' => 'Tugasan ini telah selesai dan tidak boleh dibuka semula.']);
                }

                $completedAt = $this->dates->nowUtc();
                $watermarkText = $completedAt->setTimezone($this->dates->timezone())->format('d/m/Y H:i');

                foreach ($photos as $photo) {
                    $stored = $this->storePhoto($photo, $date, 'daily', $watermarkText);
                    $storedPaths[] = $stored['path'];
                    DailyTaskEvidence::query()->create([
                        'daily_checklist_id' => $locked->id,
                        ...$stored,
                    ]);
                }

                $locked->forceFill([
                    'is_completed' => true,
                    'completed_at' => $completedAt,
                    'completion_note' => $note,
                    'completed_by_user_id' => null,
                ])->save();

                $this->audits->cleaner('task.completed', $locked, [
                    'task_type' => 'daily',
                    'task_name' => $locked->task_name,
                    'task_date' => $date,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteStored($storedPaths);
            throw $exception;
        }

        $this->checkAndNotifyChecklistCompleted($date);
    }

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function completeWeekly(WeeklyTaskOccurrence $occurrence, string $date, array $photos, ?string $note = null): void
    {
        $this->assertWritableDate($date);
        $note = $this->normaliseNote($note);
        $today = $this->dates->fromDateString($date);
        $this->weekly->advanceThrough($today);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($occurrence, $date, $photos, $note, &$storedPaths): void {
                $this->materializer->acquireTemplateSynchronizationLock();
                $this->assertAvailableDate($date);
                $locked = WeeklyTaskOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

                $today = $this->dates->today();
                $isCurrentWeek = ! $locked->week_start->greaterThan($today)
                    && ! $locked->week_start->endOfWeek()->lessThan($today);
                $isCarryoverDueToday = $locked->scheduled_date->isSameDay($today)
                    && $locked->week_start->lessThan($today->startOfWeek());

                if ($locked->status !== 'pending' || (! $isCurrentWeek && ! $isCarryoverDueToday)) {
                    throw ValidationException::withMessages(['task' => 'Tugasan mingguan ini tidak boleh diselesaikan pada hari ini.']);
                }

                $completedAt = $this->dates->nowUtc();
                $watermarkText = $completedAt->setTimezone($this->dates->timezone())->format('d/m/Y H:i');

                foreach ($photos as $photo) {
                    $stored = $this->storePhoto($photo, $date, 'weekly', $watermarkText);
                    $storedPaths[] = $stored['path'];
                    WeeklyTaskEvidence::query()->create([
                        'weekly_task_occurrence_id' => $locked->id,
                        ...$stored,
                    ]);
                }

                $locked->forceFill([
                    'status' => 'completed',
                    'completed_at' => $completedAt,
                    'completed_on' => $date,
                    'completion_note' => $note,
                ])->save();

                $this->audits->cleaner('task.completed', $locked, [
                    'task_type' => 'weekly',
                    'task_name' => $locked->task_name,
                    'task_date' => $date,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteStored($storedPaths);
            throw $exception;
        }

        $this->checkAndNotifyChecklistCompleted($date);
    }

    /**
     * @param  list<UploadedFile>  $photos
     */
    public function completeMonthly(MonthlyTaskOccurrence $occurrence, string $date, array $photos, ?string $note = null): void
    {
        $this->assertWritableDate($date);
        $note = $this->normaliseNote($note);
        $today = $this->dates->fromDateString($date);
        $this->monthly->advanceThrough($today);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($occurrence, $date, $photos, $note, &$storedPaths): void {
                $this->materializer->acquireTemplateSynchronizationLock();
                $this->assertAvailableDate($date);
                $locked = MonthlyTaskOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

                $today = $this->dates->today();
                $isCurrentMonth = ! $locked->month_start->greaterThan($today)
                    && ! $locked->month_start->endOfMonth()->lessThan($today);
                $isCarryoverDueToday = $locked->scheduled_date->isSameDay($today)
                    && $locked->month_start->lessThan($today->startOfMonth());

                if ($locked->status !== 'pending' || (! $isCurrentMonth && ! $isCarryoverDueToday)) {
                    throw ValidationException::withMessages(['task' => 'Tugasan bulanan ini tidak boleh diselesaikan pada hari ini.']);
                }

                $completedAt = $this->dates->nowUtc();
                $watermarkText = $completedAt->setTimezone($this->dates->timezone())->format('d/m/Y H:i');

                foreach ($photos as $photo) {
                    $stored = $this->storePhoto($photo, $date, 'monthly', $watermarkText);
                    $storedPaths[] = $stored['path'];
                    MonthlyTaskEvidence::query()->create([
                        'monthly_task_occurrence_id' => $locked->id,
                        ...$stored,
                    ]);
                }

                $locked->forceFill([
                    'status' => 'completed',
                    'completed_at' => $completedAt,
                    'completed_on' => $date,
                    'completion_note' => $note,
                ])->save();

                $this->audits->cleaner('task.completed', $locked, [
                    'task_type' => 'monthly',
                    'task_name' => $locked->task_name,
                    'task_date' => $date,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            $this->deleteStored($storedPaths);
            throw $exception;
        }

        $this->checkAndNotifyChecklistCompleted($date);
    }

    private function checkAndNotifyChecklistCompleted(string $date): void
    {
        try {
            $targetDate = $this->dates->fromDateString($date);
            $daily = $this->materializer->forDate($targetDate);
            $weekly = $this->weekly->forChecklistDate($targetDate);
            $monthly = $this->monthly->forChecklistDate($targetDate);

            $totalTasks = $daily->count() + $weekly->count() + $monthly->count();
            if ($totalTasks === 0) {
                return;
            }

            $allDailyCompleted = $daily->every(fn (DailyChecklist $task) => (bool) $task->is_completed);
            $allWeeklyCompleted = $weekly->every(fn (WeeklyTaskOccurrence $occurrence) => $occurrence->status === 'completed');
            $allMonthlyCompleted = $monthly->every(fn (MonthlyTaskOccurrence $occurrence) => $occurrence->status === 'completed');

            if ($allDailyCompleted && $allWeeklyCompleted && $allMonthlyCompleted) {
                $formattedDate = $targetDate->format('d/m/Y');
                $this->webPush->notifyAdmins(
                    '🎉 Senarai Semak Selesai!',
                    "Semua tugasan untuk tarikh {$formattedDate} telah diselesaikan sepenuhnya.",
                    route('checklist.index', ['date' => $date]),
                    [
                        'tag' => "checklist-complete-{$date}",
                        'data' => ['date' => $date],
                    ]
                );
            }
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send checklist completion push notification', [
                'error' => $e->getMessage(),
                'date' => $date,
            ]);
        }
    }

    private function assertWritableDate(string $date): void
    {
        if (! $this->dates->isToday($date)) {
            abort(403, 'Hanya senarai semak hari ini boleh dikemas kini.');
        }

        if (! $this->calendar->isWorkingDay($date)) {
            abort(403, 'Tugasan hanya boleh dikemas kini pada hari bekerja.');
        }

    }

    private function assertAvailableDate(string $date): void
    {
        if (ChecklistDayStatus::query()->whereDate('date', $date)->where('is_unavailable', true)->exists()) {
            throw ValidationException::withMessages(['date' => 'Hari ini ditandakan MC/tidak tersedia.']);
        }
    }

    private function normaliseNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    /**
     * @return array{disk: string, path: string, mime_type: string, size_bytes: int}
     */
    private function storePhoto(UploadedFile $photo, string $date, string $type, string $watermarkText): array
    {
        $watermarked = $this->watermarker->watermark($photo, $watermarkText);

        $directory = sprintf('evidence/%s/%s/%s', $date, $type, substr(bin2hex(random_bytes(8)), 0, 2));
        $path = $directory.'/'.bin2hex(random_bytes(24)).'.'.$watermarked['extension'];
        $written = Storage::disk('local')->put($path, $watermarked['contents']);

        if (! $written) {
            throw new RuntimeException('Foto bukti tidak dapat disimpan.');
        }

        return [
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $watermarked['mime_type'],
            'size_bytes' => $watermarked['size_bytes'],
        ];
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteStored(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }
}
