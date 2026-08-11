<?php

namespace Tests\Feature;

use App\Models\ChecklistDayStatus;
use App\Models\DailyChecklist;
use App\Models\AuditLog;
use App\Models\PublicHoliday;
use App\Models\RotationCycleSetting;
use App\Models\TaskCollection;
use App\Models\TaskCollectionSchedule;
use App\Models\TaskSession;
use App\Models\TaskReopenAudit;
use App\Models\TaskTemplate;
use App\Models\WeeklyTaskOccurrence;
use App\Models\WeeklyTaskPostponement;
use App\Models\WeeklyTaskTemplate;
use App\Services\ChecklistMaterializer;
use App\Services\ChecklistWorkflow;
use App\Services\EvidenceWatermarker;
use App\Services\OperationalDate;
use App\Services\StatisticsService;
use App\Services\TaskCollectionResolver;
use App\Services\WeeklyTaskScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChecklistWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('checklist.timezone', 'Asia/Kuala_Lumpur');
        config()->set('checklist.past_materialization_days', 365);
        config()->set('checklist.future_materialization_days', 365);
        config()->set('checklist.admin_password', 'test-master-password');
        app()->setLocale('ms');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 09:00:00.123456', 'Asia/Kuala_Lumpur'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_default_sessions_and_credit_snapshots_are_materialized(): void
    {
        $this->assertSame(['Pagi', 'Tengah Hari', 'Petang'], TaskSession::query()->orderBy('sort_order')->pluck('name')->all());
        $template = $this->dailyTemplate('Bersihkan kaca', 'Pagi', 1.5);

        $sheet = app(ChecklistMaterializer::class)->forDate(app(OperationalDate::class)->today());

        $this->assertCount(1, $sheet);
        $this->assertSame($template->id, $sheet->sole()->task_template_id);
        $this->assertSame('Pagi', $sheet->sole()->session_name);
        $this->assertSame('1.50', $sheet->sole()->credit_hours);
    }

    public function test_weekends_do_not_materialize_current_or_future_daily_tasks(): void
    {
        $template = $this->dailyTemplate('Weekday only');
        $saturday = CarbonImmutable::parse('2026-07-18', 'Asia/Kuala_Lumpur');

        $items = app(ChecklistMaterializer::class)->forDate($saturday);

        $this->assertCount(0, $items);
        $this->assertDatabaseHas('checklist_materializations', ['date' => $saturday->toDateString()]);
        $this->assertDatabaseMissing('daily_checklists', [
            'task_template_id' => $template->id,
            'date' => $saturday->toDateString(),
        ]);
    }

    public function test_public_holiday_management_requires_an_admin_session(): void
    {
        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-16',
            'name' => 'Office closure',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('password');
    }

    public function test_admin_can_manage_a_future_weekday_public_holiday(): void
    {
        $this->loginAdmin();

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-16',
            'name' => ' <strong> Office closure </strong> ',
        ])->assertRedirect(route('admin.index'));

        $holiday = PublicHoliday::query()->whereDate('date', '2026-07-16')->sole();
        $this->assertSame('Office closure', $holiday->name);

        $this->get(route('admin.index', ['date' => '2026-07-16']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('publicHolidays.0.id', $holiday->id)
                ->where('publicHolidays.0.date', '2026-07-16')
                ->where('publicHolidays.0.name', 'Office closure')
                ->where('publicHolidays.0.isEditable', true)
                ->where('publicHoliday.id', $holiday->id)
                ->where('publicHoliday.date', '2026-07-16')
                ->where('publicHoliday.name', 'Office closure'));

        $this->patch(route('admin.public-holidays.update', $holiday), [
            'date' => '2026-07-17',
            'name' => ' <em> Rescheduled closure </em> ',
        ])->assertRedirect(route('admin.index'));

        $this->assertSame('2026-07-17', $holiday->refresh()->date->toDateString());
        $this->assertSame('Rescheduled closure', $holiday->name);

        $this->delete(route('admin.public-holidays.destroy', $holiday))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('public_holidays', ['id' => $holiday->id]);
    }

    public function test_public_holiday_validation_requires_a_unique_future_weekday_named_closure_and_locks_old_dates(): void
    {
        $this->loginAdmin();

        $this->post(route('admin.public-holidays.store'), [
            'date' => '16/07/2026',
            'name' => '',
        ])->assertSessionHasErrors(['date', 'name']);

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-16',
            'name' => str_repeat('A', 101),
        ])->assertSessionHasErrors('name');

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-18',
            'name' => 'Weekend closure',
        ])->assertSessionHasErrors('date');

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-15',
            'name' => 'Today closure',
        ])->assertSessionHasErrors('date');

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-14',
            'name' => 'Past closure',
        ])->assertSessionHasErrors('date');

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-16',
            'name' => 'Office closure',
        ])->assertRedirect(route('admin.index'));

        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-16',
            'name' => 'Duplicate closure',
        ])->assertSessionHasErrors('date');

        $currentHoliday = PublicHoliday::query()->create([
            'date' => '2026-07-15',
            'name' => 'Existing current closure',
        ]);
        $pastHoliday = PublicHoliday::query()->create([
            'date' => '2026-07-14',
            'name' => 'Existing past closure',
        ]);

        $this->patch(route('admin.public-holidays.update', $currentHoliday), [
            'date' => '2026-07-17',
            'name' => 'Attempted current update',
        ])->assertSessionHasErrors('date');
        $this->delete(route('admin.public-holidays.destroy', $currentHoliday))
            ->assertSessionHasErrors('date');

        $this->patch(route('admin.public-holidays.update', $pastHoliday), [
            'date' => '2026-07-17',
            'name' => 'Attempted past update',
        ])->assertSessionHasErrors('date');
        $this->delete(route('admin.public-holidays.destroy', $pastHoliday))
            ->assertSessionHasErrors('date');

        $this->assertSame('2026-07-15', $currentHoliday->refresh()->date->toDateString());
        $this->assertSame('2026-07-14', $pastHoliday->refresh()->date->toDateString());
    }

    public function test_future_public_holiday_suppresses_daily_work_and_restores_it_when_moved_or_removed(): void
    {
        $initialDate = CarbonImmutable::parse('2026-07-16', 'Asia/Kuala_Lumpur');
        $movedDate = CarbonImmutable::parse('2026-07-17', 'Asia/Kuala_Lumpur');
        $template = $this->dailyTemplate('Holiday-sensitive daily task');
        $initialTask = app(ChecklistMaterializer::class)->forDate($initialDate)->sole();
        $movedTask = app(ChecklistMaterializer::class)->forDate($movedDate)->sole();

        DB::table('checklist_item_positions')->insert([
            'date' => $initialDate->toDateString(),
            'task_session_id' => $initialTask->task_session_id,
            'item_type' => 'daily',
            'item_id' => $initialTask->id,
            'position' => 1,
            'created_at' => app(OperationalDate::class)->nowUtc(),
            'updated_at' => app(OperationalDate::class)->nowUtc(),
        ]);

        $this->loginAdmin();
        $this->post(route('admin.public-holidays.store'), [
            'date' => $initialDate->toDateString(),
            'name' => 'Office closure',
        ])->assertRedirect(route('admin.index'));

        $holiday = PublicHoliday::query()->whereDate('date', $initialDate->toDateString())->sole();
        $this->assertDatabaseMissing('daily_checklists', ['id' => $initialTask->id]);
        $this->assertDatabaseMissing('checklist_item_positions', ['item_type' => 'daily', 'item_id' => $initialTask->id]);
        $this->assertCount(0, app(ChecklistWorkflow::class)->forDate($initialDate)['daily']);

        $this->patch(route('admin.public-holidays.update', $holiday), [
            'date' => $movedDate->toDateString(),
            'name' => 'Rescheduled closure',
        ])->assertRedirect(route('admin.index'));

        $this->assertTrue(app(ChecklistMaterializer::class)
            ->forDate($initialDate)
            ->contains('task_template_id', $template->id));
        $this->assertDatabaseMissing('daily_checklists', ['id' => $movedTask->id]);
        $this->assertCount(0, app(ChecklistWorkflow::class)->forDate($movedDate)['daily']);

        $this->delete(route('admin.public-holidays.destroy', $holiday))
            ->assertRedirect(route('admin.index'));

        $this->assertTrue(app(ChecklistMaterializer::class)
            ->forDate($movedDate)
            ->contains('task_template_id', $template->id));
    }

    public function test_public_holidays_deny_stale_direct_task_completion_and_reordering(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask('Stale holiday task');
        $date = $task->date->toDateString();
        PublicHoliday::query()->create([
            'date' => $date,
            'name' => 'Existing office closure',
        ]);

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $date,
            'photos' => [$this->proof()],
        ]);

        $this->assertFalse($task->refresh()->is_completed);

        $this->post(route('checklist.order'), [
            'date' => $date,
            'task_session_id' => $task->task_session_id,
            'items' => [['type' => 'daily', 'id' => $task->id]],
        ]);

        $this->assertDatabaseMissing('checklist_item_positions', [
            'item_type' => 'daily',
            'item_id' => $task->id,
        ]);
    }

    public function test_public_holidays_postpone_weekly_work_across_weekend_and_keep_it_orderable_and_completable(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $template = $this->weeklyTemplate('Weekly closure carry-over', dueWeekday: CarbonImmutable::FRIDAY);
        $scheduler = app(WeeklyTaskScheduler::class);
        $scheduler->materializeWeek(app(OperationalDate::class)->today());
        $occurrence = WeeklyTaskOccurrence::query()
            ->where('weekly_task_template_id', $template->id)
            ->sole();

        $this->loginAdmin();
        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-17',
            'name' => 'Friday closure',
        ])->assertRedirect(route('admin.index'));
        $this->post(route('admin.public-holidays.store'), [
            'date' => '2026-07-20',
            'name' => 'Monday closure',
        ])->assertRedirect(route('admin.index'));

        $occurrence->refresh();
        $this->assertSame('2026-07-17', $occurrence->original_due_date->toDateString());
        $this->assertSame('2026-07-21', $occurrence->scheduled_date->toDateString());
        $this->assertSame('pending', $occurrence->status);
        $this->assertCount(0, app(ChecklistWorkflow::class)
            ->forDate(CarbonImmutable::parse('2026-07-17', 'Asia/Kuala_Lumpur'))['weekly']);
        $this->assertCount(0, app(ChecklistWorkflow::class)
            ->forDate(CarbonImmutable::parse('2026-07-20', 'Asia/Kuala_Lumpur'))['weekly']);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-21 09:00:00.123456', 'Asia/Kuala_Lumpur'));
        $date = app(OperationalDate::class)->today()->toDateString();
        $items = app(ChecklistWorkflow::class)->forDate(app(OperationalDate::class)->today());
        $currentWeekOccurrence = WeeklyTaskOccurrence::query()
            ->where('weekly_task_template_id', $template->id)
            ->whereDate('week_start', app(OperationalDate::class)->today()->startOfWeek()->toDateString())
            ->sole();

        $this->assertTrue($items['weekly']->contains('id', $occurrence->id));
        $this->assertTrue($items['weekly']->contains('id', $currentWeekOccurrence->id));

        $this->post(route('checklist.order'), [
            'date' => $date,
            'task_session_id' => $occurrence->task_session_id,
            'items' => [
                ['type' => 'weekly', 'id' => $occurrence->id],
                ['type' => 'weekly', 'id' => $currentWeekOccurrence->id],
            ],
        ])->assertRedirect(route('checklist.index', ['date' => $date]));

        $this->assertTrue(DB::table('checklist_item_positions')
            ->whereDate('date', $date)
            ->where('item_type', 'weekly')
            ->where('item_id', $occurrence->id)
            ->where('position', 1)
            ->exists());

        $this->post(route('tasks.weekly.complete', $occurrence), [
            'date' => $date,
            'photos' => [$this->proof()],
        ])->assertRedirect(route('checklist.index', ['date' => $date]));

        $this->assertSame('completed', $occurrence->refresh()->status);
        $this->assertSame($date, $occurrence->completed_on->toDateString());
    }

    public function test_rotations_and_checklists_share_the_server_resolved_sunday_cycle(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        RotationCycleSetting::query()->updateOrCreate(['id' => 1], ['anchor_week_start' => '2026-07-12']);
        $this->loginAdmin();
        $this->post(route('admin.collections.store'), ['name' => 'Rotation A'])->assertRedirect(route('admin.index'));
        $this->post(route('admin.collections.store'), ['name' => 'Rotation B'])->assertRedirect(route('admin.index'));
        $this->post(route('admin.collections.store'), ['name' => 'Rotation C'])->assertRedirect(route('admin.index'));

        $a = TaskCollection::query()->where('name', 'Rotation A')->sole();
        $b = TaskCollection::query()->where('name', 'Rotation B')->sole();
        $c = TaskCollection::query()->where('name', 'Rotation C')->sole();
        $this->assertSame('2026-07-12', RotationCycleSetting::query()->findOrFail(1)->anchor_week_start->toDateString());
        $this->assertSame([$a->id, $b->id, $c->id], TaskCollection::query()
            ->where('is_default', false)
            ->orderBy('rotation_order')
            ->pluck('id')
            ->all());
        $resolver = new TaskCollectionResolver(app(OperationalDate::class));
        $today = app(OperationalDate::class)->today();

        $this->assertSame('2026-07-15', $today->toDateString());
        $this->assertSame($a->id, $resolver->forDate($today)->id, 'Anchor week should resolve Rotation A.');
        $this->assertSame($b->id, $resolver->forDate(CarbonImmutable::parse('2026-07-19', 'Asia/Kuala_Lumpur'))->id, 'Second cycle week should resolve Rotation B.');
        $this->assertSame($c->id, $resolver->forDate(CarbonImmutable::parse('2026-07-26', 'Asia/Kuala_Lumpur'))->id, 'Third cycle week should resolve Rotation C.');
        $this->assertSame($a->id, $resolver->forDate(CarbonImmutable::parse('2026-08-02', 'Asia/Kuala_Lumpur'))->id, 'Cycle should return to Rotation A.');

        $daily = $this->dailyTemplate('Rotation A daily');
        $daily->taskCollections()->sync([$a->id]);
        $weekly = $this->weeklyTemplate('Rotation B weekly', 1);
        $weekly->taskCollections()->sync([$b->id]);

        $this->assertTrue(app(ChecklistMaterializer::class)->forDate($today)->contains('task_template_id', $daily->id));
        $this->assertTrue(app(WeeklyTaskScheduler::class)
            ->forChecklistDate(CarbonImmutable::parse('2026-07-20', 'Asia/Kuala_Lumpur'))
            ->contains('weekly_task_template_id', $weekly->id));

        $calendarResponse = $this->get(route('admin.index', ['rotation_month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('rotationCalendar.month', '2026-07')
                ->has('rotationCalendar.weeks', 5));
        $calendar = $calendarResponse->inertiaProps('rotationCalendar');

        $this->assertSame('2026-07-12', $calendar['weeks'][2]['weekStart']);
        $this->assertSame($a->id, $calendar['weeks'][2]['rotation']['id']);
        $this->assertSame($c->id, $calendar['weeks'][4]['rotation']['id']);

        $augustResponse = $this->get(route('admin.index', ['rotation_month' => '2026-08']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('rotationCalendar.month', '2026-08')
                ->has('rotationCalendar.weeks', 6));
        $august = $augustResponse->inertiaProps('rotationCalendar');

        $this->assertSame('2026-07-26', $august['weeks'][0]['weekStart']);
        $this->assertSame($c->id, $august['weeks'][0]['rotation']['id']);
        $this->assertSame([false, false, false, false, false, false, true], array_column($august['weeks'][0]['days'], 'inMonth'));
        $this->assertSame('2026-08-30', $august['weeks'][5]['weekStart']);
        $this->assertSame($b->id, $august['weeks'][5]['rotation']['id']);
        $this->assertSame([true, true, false, false, false, false, false], array_column($august['weeks'][5]['days'], 'inMonth'));
    }

    public function test_rotation_snapshot_repair_rebuilds_incomplete_current_tasks_with_the_correct_rotation(): void
    {
        $dates = app(OperationalDate::class);
        $today = $dates->today();
        $weekStart = $today->startOfWeek(CarbonImmutable::MONDAY);
        RotationCycleSetting::query()->updateOrCreate([
            'id' => 1,
        ], [
            'anchor_week_start' => $today->startOfWeek(CarbonImmutable::SUNDAY)->toDateString(),
        ]);

        $a = TaskCollection::query()->create(['name' => 'Rotation A', 'is_default' => false, 'rotation_order' => 1]);
        TaskCollection::query()->create(['name' => 'Rotation B', 'is_default' => false, 'rotation_order' => 2]);
        $c = TaskCollection::query()->create(['name' => 'Rotation C', 'is_default' => false, 'rotation_order' => 3]);

        $aDaily = $this->dailyTemplate('Rotation A daily');
        $aDaily->forceFill(['task_collection_id' => $a->id])->save();
        $aDaily->taskCollections()->sync([$a->id]);

        $cDaily = $this->dailyTemplate('Stale Rotation C daily');
        $cDaily->forceFill(['task_collection_id' => $c->id])->save();
        $cDaily->taskCollections()->sync([$c->id]);

        $completedCDaily = $this->dailyTemplate('Completed Rotation C daily');
        $completedCDaily->forceFill(['task_collection_id' => $c->id])->save();
        $completedCDaily->taskCollections()->sync([$c->id]);

        $staleDaily = DailyChecklist::query()->create([
            'date' => $today->toDateString(),
            'task_template_id' => $cDaily->id,
            'task_name' => $cDaily->task_name,
            'task_session_id' => $cDaily->task_session_id,
            'session_name' => $cDaily->taskSession->name,
            'credit_hours' => $cDaily->credit_hours,
            'is_completed' => false,
        ]);
        $completedDaily = DailyChecklist::query()->create([
            'date' => $today->toDateString(),
            'task_template_id' => $completedCDaily->id,
            'task_name' => $completedCDaily->task_name,
            'task_session_id' => $completedCDaily->task_session_id,
            'session_name' => $completedCDaily->taskSession->name,
            'credit_hours' => $completedCDaily->credit_hours,
            'is_completed' => true,
            'completed_at' => $dates->nowUtc(),
        ]);
        DB::table('checklist_materializations')->insert(['date' => $today->toDateString()]);

        $aWeekly = $this->weeklyTemplate('Rotation A weekly', CarbonImmutable::WEDNESDAY);
        $aWeekly->forceFill(['task_collection_id' => $a->id])->save();
        $aWeekly->taskCollections()->sync([$a->id]);

        $cWeekly = $this->weeklyTemplate('Stale Rotation C weekly', CarbonImmutable::WEDNESDAY);
        $cWeekly->forceFill(['task_collection_id' => $c->id])->save();
        $cWeekly->taskCollections()->sync([$c->id]);

        $completedCWeekly = $this->weeklyTemplate('Completed Rotation C weekly', CarbonImmutable::WEDNESDAY);
        $completedCWeekly->forceFill(['task_collection_id' => $c->id])->save();
        $completedCWeekly->taskCollections()->sync([$c->id]);

        $dueDate = $weekStart->addDays(CarbonImmutable::WEDNESDAY - CarbonImmutable::MONDAY);
        $staleWeekly = WeeklyTaskOccurrence::query()->create([
            'week_start' => $weekStart->toDateString(),
            'weekly_task_template_id' => $cWeekly->id,
            'task_session_id' => $cWeekly->task_session_id,
            'task_name' => $cWeekly->task_name,
            'session_name' => $cWeekly->taskSession->name,
            'credit_hours' => $cWeekly->credit_hours,
            'original_due_date' => $dueDate->toDateString(),
            'scheduled_date' => $dueDate->toDateString(),
            'status' => 'pending',
        ]);
        $completedWeekly = WeeklyTaskOccurrence::query()->create([
            'week_start' => $weekStart->toDateString(),
            'weekly_task_template_id' => $completedCWeekly->id,
            'task_session_id' => $completedCWeekly->task_session_id,
            'task_name' => $completedCWeekly->task_name,
            'session_name' => $completedCWeekly->taskSession->name,
            'credit_hours' => $completedCWeekly->credit_hours,
            'original_due_date' => $dueDate->toDateString(),
            'scheduled_date' => $dueDate->toDateString(),
            'status' => 'completed',
            'completed_at' => $dates->nowUtc(),
            'completed_on' => $today->toDateString(),
        ]);
        DB::table('weekly_materializations')->insert(['week_start' => $weekStart->toDateString()]);

        $migration = require database_path('migrations/2026_08_05_000011_rebuild_rotation_snapshots_after_anchor_timezone_fix.php');
        $migration->up();

        $this->assertDatabaseMissing('daily_checklists', ['id' => $staleDaily->id]);
        $this->assertDatabaseHas('daily_checklists', ['id' => $completedDaily->id]);
        $this->assertDatabaseMissing('checklist_materializations', ['date' => $today->toDateString()]);
        $this->assertDatabaseMissing('weekly_task_occurrences', ['id' => $staleWeekly->id]);
        $this->assertDatabaseHas('weekly_task_occurrences', ['id' => $completedWeekly->id]);
        $this->assertDatabaseMissing('weekly_materializations', ['week_start' => $weekStart->toDateString()]);

        $checklist = app(ChecklistWorkflow::class)->forDate($today);

        $this->assertTrue($checklist['daily']->contains('task_template_id', $aDaily->id));
        $this->assertTrue($checklist['weekly']->contains('weekly_task_template_id', $aWeekly->id));
    }

    public function test_audit_log_records_login_attempts_and_admin_state_changes_without_credentials(): void
    {
        $this->post(route('admin.login'), ['password' => 'not-the-password'])->assertSessionHasErrors('password');
        $this->loginAdmin();
        $this->post(route('admin.sessions.store'), ['name' => 'Malam'])->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_failed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.login_succeeded']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'session.created']);
        $this->assertArrayNotHasKey('password', AuditLog::query()->where('action', 'admin.login_failed')->sole()->metadata ?? []);
    }

    public function test_admin_can_manage_daily_templates_with_relational_sessions_and_credits(): void
    {
        $session = $this->taskSession();
        $this->loginAdmin();

        $this->post(route('admin.templates.store'), [
            'task_name' => ' Mop lobi ',
            'task_session_id' => $session->id,
            'applies_to_all_collections' => false,
            'task_collection_ids' => [$this->defaultCollection()->id],
            'credit_hours' => 1.25,
        ])->assertRedirect(route('admin.index'));

        $template = TaskTemplate::query()->where('task_name', 'Mop lobi')->sole();
        $this->assertSame($session->id, $template->task_session_id);
        $this->assertSame('1.25', $template->credit_hours);

        $this->post(route('admin.templates.store'), [
            'task_name' => 'Invalid credit',
            'task_session_id' => $session->id,
            'applies_to_all_collections' => false,
            'task_collection_ids' => [$this->defaultCollection()->id],
            'credit_hours' => 1.1,
        ])->assertSessionHasErrors('credit_hours');
    }

    public function test_session_archive_is_blocked_until_tasks_are_reassigned(): void
    {
        $session = $this->taskSession();
        $this->dailyTemplate('Task using session', $session->name);
        $this->loginAdmin();

        $this->delete(route('admin.sessions.destroy', $session))
            ->assertSessionHasErrors('session');
        $this->assertTrue($session->refresh()->is_active);
    }

    public function test_admin_can_create_rename_reorder_and_archive_an_unused_session(): void
    {
        $this->loginAdmin();
        $this->post(route('admin.sessions.store'), ['name' => 'Malam'])->assertRedirect(route('admin.index'));
        $session = TaskSession::query()->where('name', 'Malam')->sole();

        $this->patch(route('admin.sessions.update', $session), ['name' => 'Lewat Malam'])
            ->assertRedirect(route('admin.index'));
        $orderedIds = TaskSession::query()->active()->orderByDesc('sort_order')->pluck('id')->all();
        $this->patch(route('admin.sessions.reorder'), ['session_ids' => $orderedIds])
            ->assertRedirect(route('admin.index'));
        $this->delete(route('admin.sessions.destroy', $session))->assertRedirect(route('admin.index'));

        $this->assertSame('Lewat Malam', $session->refresh()->name);
        $this->assertFalse($session->is_active);
    }

    public function test_admin_weekly_crud_materializes_the_current_week_when_due_day_is_ahead(): void
    {
        $this->loginAdmin();
        $session = $this->taskSession();

        $this->post(route('admin.weekly-templates.store'), [
            'task_name' => 'Cuci kipas',
            'task_session_id' => $session->id,
            'applies_to_all_collections' => false,
            'task_collection_ids' => [$this->defaultCollection()->id],
            'due_weekday' => 5,
            'credit_hours' => 2.5,
        ])->assertRedirect(route('admin.index'));

        $template = WeeklyTaskTemplate::query()->where('task_name', 'Cuci kipas')->sole();
        $this->assertSame(
            '2026-07-17',
            WeeklyTaskOccurrence::query()->where('weekly_task_template_id', $template->id)->sole()->original_due_date->toDateString(),
        );

        $this->patch(route('admin.weekly-templates.update', $template), [
            'task_name' => 'Cuci semua kipas',
            'task_session_id' => $session->id,
            'applies_to_all_collections' => false,
            'task_collection_ids' => [$this->defaultCollection()->id],
            'due_weekday' => 4,
            'credit_hours' => 3,
        ])->assertRedirect(route('admin.index'));
        $this->assertSame('Cuci semua kipas', $template->refresh()->task_name);

        $this->delete(route('admin.weekly-templates.destroy', $template))->assertRedirect(route('admin.index'));
        $this->assertFalse($template->refresh()->is_active);
    }

    public function test_admin_can_delete_an_unused_non_default_collection(): void
    {
        $this->loginAdmin();
        $collection = TaskCollection::query()->create([
            'name' => 'Collection C',
            'is_default' => false,
        ]);

        $this->delete(route('admin.collections.destroy', $collection))
            ->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('task_collections', [
            'id' => $collection->id,
        ]);
    }

    public function test_admin_cannot_delete_default_or_used_collection(): void
    {
        $this->loginAdmin();
        $defaultCollection = $this->defaultCollection();
        $usedCollection = TaskCollection::query()->create([
            'name' => 'Collection D',
            'is_default' => false,
        ]);

        $template = TaskTemplate::query()->create([
            'task_name' => 'Used daily',
            'task_session_id' => $this->taskSession()->id,
            'task_collection_id' => $usedCollection->id,
            'applies_to_all_collections' => false,
            'credit_hours' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $template->taskCollections()->sync([$usedCollection->id]);

        $this->delete(route('admin.collections.destroy', $defaultCollection))
            ->assertSessionHasErrors('collection');

        $this->delete(route('admin.collections.destroy', $usedCollection))
            ->assertSessionHasErrors('collection');

        $this->assertDatabaseHas('task_collections', [
            'id' => $defaultCollection->id,
        ]);
        $this->assertDatabaseHas('task_collections', [
            'id' => $usedCollection->id,
        ]);
    }

    public function test_collection_schedule_switches_daily_and_weekly_tasks_by_date_range(): void
    {
        $session = $this->taskSession();
        $defaultCollection = $this->defaultCollection();
        $alt = TaskCollection::query()->create([
            'name' => 'Collection B',
            'is_default' => false,
        ]);

        $defaultWeekly = WeeklyTaskTemplate::query()->create([
            'task_name' => 'Default weekly',
            'task_session_id' => $session->id,
            'task_collection_id' => $defaultCollection->id,
            'applies_to_all_collections' => false,
            'due_weekday' => 5,
            'credit_hours' => 2,
            'sort_order' => 1,
            'starts_on' => '2026-07-13',
            'is_active' => true,
        ]);
        $defaultWeekly->taskCollections()->sync([$defaultCollection->id]);

        $defaultDaily = TaskTemplate::query()->create([
            'task_name' => 'Default daily',
            'task_session_id' => $session->id,
            'task_collection_id' => $defaultCollection->id,
            'applies_to_all_collections' => false,
            'credit_hours' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $defaultDaily->taskCollections()->sync([$defaultCollection->id]);

        $altWeekly = WeeklyTaskTemplate::query()->create([
            'task_name' => 'Collection B weekly',
            'task_session_id' => $session->id,
            'task_collection_id' => $alt->id,
            'applies_to_all_collections' => false,
            'due_weekday' => 5,
            'credit_hours' => 2,
            'sort_order' => 2,
            'starts_on' => '2026-08-03',
            'is_active' => true,
        ]);
        $altWeekly->taskCollections()->sync([$alt->id]);

        $altDaily = TaskTemplate::query()->create([
            'task_name' => 'Collection B daily',
            'task_session_id' => $session->id,
            'task_collection_id' => $alt->id,
            'applies_to_all_collections' => false,
            'credit_hours' => 1,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $altDaily->taskCollections()->sync([$alt->id]);

        TaskCollectionSchedule::query()->create([
            'task_collection_id' => $alt->id,
            'starts_on' => '2026-08-03',
            'ends_on' => '2026-08-09',
        ]);

        $workflow = app(\App\Services\ChecklistWorkflow::class);
        $items = $workflow->forDate(CarbonImmutable::parse('2026-08-07', 'Asia/Kuala_Lumpur'));

        $this->assertSame(['Collection B daily'], $items['daily']->pluck('task_name')->all());
        $this->assertSame(['Collection B weekly'], $items['weekly']->pluck('task_name')->all());
    }

    public function test_weekly_task_misses_before_the_weekend_without_being_postponed_to_it(): void
    {
        $template = $this->weeklyTemplate('Cuci stor', dueWeekday: 5);
        $scheduler = app(WeeklyTaskScheduler::class);
        $wednesday = app(OperationalDate::class)->today();

        $items = $scheduler->forChecklistDate($wednesday);
        $this->assertCount(1, $items);
        $this->assertSame('2026-07-17', $items->sole()->original_due_date->toDateString());

        $scheduler->advanceThrough(CarbonImmutable::parse('2026-07-18', 'Asia/Kuala_Lumpur'));
        $occurrence = WeeklyTaskOccurrence::query()->where('weekly_task_template_id', $template->id)->firstOrFail();
        $this->assertSame('2026-07-17', $occurrence->scheduled_date->toDateString());
        $this->assertSame('missed', $occurrence->status);
        $this->assertCount(0, $occurrence->postponements);
        $this->assertCount(0, $scheduler->forChecklistDate(CarbonImmutable::parse('2026-07-18', 'Asia/Kuala_Lumpur')));
    }

    public function test_mc_locks_the_day_and_moves_a_due_weekly_task(): void
    {
        $this->dailyTemplate('Harian');
        $weekly = $this->weeklyTemplate('Mingguan', dueWeekday: 3);
        app(ChecklistMaterializer::class)->forDate(app(OperationalDate::class)->today());
        app(WeeklyTaskScheduler::class)->materializeWeek(app(OperationalDate::class)->today());
        $today = app(OperationalDate::class)->today()->toDateString();

        $this->post(route('checklist.availability'), [
            'date' => $today,
            'is_unavailable' => true,
        ])->assertRedirect(route('checklist.index', ['date' => $today]));

        $this->assertTrue(ChecklistDayStatus::query()->whereDate('date', $today)->where('is_unavailable', true)->exists());
        $occurrence = WeeklyTaskOccurrence::query()->where('weekly_task_template_id', $weekly->id)->sole();
        $this->assertSame('2026-07-16', $occurrence->scheduled_date->toDateString());
        $this->assertDatabaseHas('weekly_task_postponements', [
            'weekly_task_occurrence_id' => $occurrence->id,
            'reason' => 'unavailable',
        ]);

        $this->post(route('checklist.availability'), [
            'date' => $today,
            'is_unavailable' => false,
        ])->assertRedirect(route('checklist.index', ['date' => $today]));
        $this->assertSame($today, $occurrence->refresh()->scheduled_date->toDateString());
        $this->assertCount(0, $occurrence->postponements);
    }

    public function test_completion_requires_private_image_evidence_and_is_permanent(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();
        $today = $task->date->toDateString();

        $this->post(route('tasks.daily.complete', $task), ['date' => $today])
            ->assertSessionHasErrors('photos');

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $today,
            'photos' => [$this->proof()],
        ])->assertRedirect(route('checklist.index', ['date' => $today]));

        $task->refresh();
        $this->assertTrue($task->is_completed);
        $evidence = $task->evidence()->sole();
        Storage::disk('local')->assertExists($evidence->path);

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $today,
            'photos' => [$this->proof()],
        ])->assertSessionHasErrors('task');
        $this->assertCount(1, $task->evidence);
    }

    public function test_evidence_can_only_be_streamed_by_an_admin(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();
        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$this->proof()],
        ]);
        $evidence = $task->evidence()->sole();

        $this->get(route('admin.evidence.daily', $evidence))->assertRedirect(route('home'));
        $this->loginAdmin();
        $this->get(route('admin.evidence.daily', $evidence))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_cleaner_note_is_saved_with_completed_evidence(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'note' => 'Area closed while cleaning.',
            'photos' => [$this->proof()],
        ])->assertRedirect(route('checklist.index', ['date' => $task->date->toDateString()]));

        $this->assertSame('Area closed while cleaning.', $task->refresh()->completion_note);
    }

    public function test_completion_accepts_up_to_five_evidence_photos(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();
        $photos = array_map(fn () => $this->proof(), range(1, 5));

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => $photos,
        ])->assertRedirect(route('checklist.index', ['date' => $task->date->toDateString()]));

        $this->assertTrue($task->refresh()->is_completed);
        $this->assertCount(5, $task->evidence);
    }

    public function test_completion_rejects_a_sixth_evidence_photo(): void
    {
        $task = $this->dailyTask();
        $photos = array_map(fn () => $this->proof(), range(1, 6));

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => $photos,
        ])->assertSessionHasErrors('photos');

        $this->assertFalse($task->refresh()->is_completed);
    }

    public function test_completion_rejects_an_evidence_photo_larger_than_ten_megabytes(): void
    {
        $task = $this->dailyTask();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nXsAAAAASUVORK5CYII=');
        $tooLarge = UploadedFile::fake()->createWithContent(
            'too-large.png',
            $png.str_repeat("\0", (10241 * 1024) - strlen($png)),
        );

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$tooLarge],
        ])->assertSessionHasErrors('photos.0');

        $this->assertFalse($task->refresh()->is_completed);
    }

    public function test_admin_can_reopen_a_current_daily_task_with_an_audit_record(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();
        $date = $task->date->toDateString();
        $this->post(route('tasks.daily.complete', $task), [
            'date' => $date,
            'note' => 'Supplies were unavailable.',
            'photos' => [$this->proof()],
        ]);
        $evidence = $task->evidence()->sole();

        $this->patch(route('admin.tasks.daily.reopen', $task), ['reason' => 'The wrong area was photographed.'])
            ->assertRedirect(route('home'));

        $this->loginAdmin();
        $this->patch(route('admin.tasks.daily.reopen', $task), ['reason' => 'The wrong area was photographed.'])
            ->assertRedirect(route('admin.index'));

        $task->refresh();
        $this->assertFalse($task->is_completed);
        $this->assertNull($task->completion_note);
        $this->assertSame(0, $task->evidence()->count());
        $this->assertNotNull($evidence->refresh()->invalidated_at);
        $this->assertSame('The wrong area was photographed.', $evidence->invalidation_reason);

        $audit = TaskReopenAudit::query()->sole();
        $this->assertSame('daily', $audit->task_type);
        $this->assertSame('Supplies were unavailable.', $audit->completion_note);
        $this->assertSame(1, $audit->invalidated_evidence_count);

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $date,
            'note' => 'Area was cleaned after the correction.',
            'photos' => [$this->proof()],
        ])->assertRedirect(route('checklist.index', ['date' => $date]));
        $this->assertTrue($task->refresh()->is_completed);
        $this->assertSame('Area was cleaned after the correction.', $task->completion_note);
    }

    public function test_completed_day_cannot_be_marked_unavailable(): void
    {
        Storage::fake('local');
        $this->fakeWatermarker();
        $task = $this->dailyTask();
        $date = $task->date->toDateString();
        $this->post(route('tasks.daily.complete', $task), ['date' => $date, 'photos' => [$this->proof()]]);

        $this->post(route('checklist.availability'), ['date' => $date, 'is_unavailable' => true])
            ->assertSessionHasErrors('is_unavailable');
        $this->assertDatabaseMissing('checklist_day_statuses', ['date' => $date, 'is_unavailable' => true]);
    }

    public function test_cleaner_can_persist_an_exact_same_session_order(): void
    {
        $first = $this->dailyTask('First');
        $second = $this->dailyTask('Second');
        $date = $first->date->toDateString();

        $this->post(route('checklist.order'), [
            'date' => $date,
            'task_session_id' => $this->taskSession()->id,
            'items' => [
                ['type' => 'daily', 'id' => $second->id],
                ['type' => 'daily', 'id' => $first->id],
            ],
        ])->assertRedirect(route('checklist.index', ['date' => $date]));

        $this->assertDatabaseHas('checklist_item_positions', ['item_type' => 'daily', 'item_id' => $second->id, 'position' => 1]);
        $this->assertDatabaseHas('checklist_item_positions', ['item_type' => 'daily', 'item_id' => $first->id, 'position' => 2]);

        $this->post(route('checklist.order'), [
            'date' => $date,
            'task_session_id' => $this->taskSession()->id,
            'items' => [['type' => 'daily', 'id' => $first->id]],
        ])->assertSessionHasErrors('items');
    }

    public function test_statistics_return_the_simplified_overview_and_selected_working_day_trend(): void
    {
        $today = app(OperationalDate::class)->today();
        DB::table('statistics_tracking')->update(['started_on' => $today->subDay()->toDateString()]);
        $past = $this->dailyTask('Past task', $today->subDay()->toDateString());
        $current = $this->dailyTask('Current task', $today->toDateString());
        DB::table('checklist_materializations')->insertOrIgnore([
            ['date' => $today->subDay()->toDateString()],
            ['date' => $today->toDateString()],
        ]);
        $past->forceFill(['is_completed' => true, 'completed_at' => $today->subDay()->setTimezone('UTC')])->save();
        ChecklistDayStatus::query()->create(['date' => $today->toDateString(), 'is_unavailable' => true]);

        $stats = app(StatisticsService::class)->build($today->subDay(), $today);

        $this->assertSame(1, $stats['overview']['completed']);
        $this->assertSame(1, $stats['overview']['pending']);
        $this->assertSame(2, $stats['overview']['totalTasks']);
        $this->assertSame(['completed', 'missed', 'pending', 'completionRate', 'totalTasks'], array_keys($stats['overview']));
        $this->assertCount(2, $stats['trend']);
        $this->assertSame([
            $today->subDay()->toDateString(),
            $today->toDateString(),
        ], array_column($stats['trend'], 'date'));
        $this->assertSame($today->toDateString(), $stats['trend'][1]['date']);
    }

    public function test_statistics_trend_omits_weekends_inside_the_selected_range(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-20 09:00:00.123456', 'Asia/Kuala_Lumpur'));
        $today = app(OperationalDate::class)->today();
        DB::table('statistics_tracking')->update(['started_on' => '2026-07-14']);

        $stats = app(StatisticsService::class)->build($today->subDays(6), $today);

        $this->assertSame([
            '2026-07-14', '2026-07-15', '2026-07-16', '2026-07-17',
            '2026-07-20',
        ], array_column($stats['trend'], 'date'));
    }

    public function test_statistics_overview_and_trend_exclude_custom_public_holidays(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-20 09:00:00.123456', 'Asia/Kuala_Lumpur'));
        $today = app(OperationalDate::class)->today();
        DB::table('statistics_tracking')->update(['started_on' => '2026-07-16']);

        $normal = $this->dailyTask('Normal completed task', '2026-07-16');
        $holidayTask = $this->dailyTask('Completed task on office closure', '2026-07-17');
        $this->dailyTask('Current pending task', $today->toDateString());
        $normal->forceFill(['is_completed' => true, 'completed_at' => $today->subDays(4)->setTimezone('UTC')])->save();
        $holidayTask->forceFill(['is_completed' => true, 'completed_at' => $today->subDays(3)->setTimezone('UTC')])->save();
        PublicHoliday::query()->create([
            'date' => '2026-07-17',
            'name' => 'Historical office closure',
        ]);
        DB::table('checklist_materializations')->insertOrIgnore([
            ['date' => '2026-07-16'],
            ['date' => '2026-07-17'],
            ['date' => '2026-07-18'],
            ['date' => '2026-07-19'],
            ['date' => $today->toDateString()],
        ]);

        $stats = app(StatisticsService::class)->build(
            CarbonImmutable::parse('2026-07-16', 'Asia/Kuala_Lumpur'),
            $today,
        );

        $this->assertSame(1, $stats['overview']['completed']);
        $this->assertSame(1, $stats['overview']['pending']);
        $this->assertSame(2, $stats['overview']['totalTasks']);
        $this->assertSame([
            '2026-07-16',
            '2026-07-20',
        ], array_column($stats['trend'], 'date'));
        $this->assertSame(0, count(array_filter(
            $stats['trend'],
            static fn (array $row): bool => $row['date'] === '2026-07-17',
        )));
    }

    public function test_admin_endpoints_require_master_session_and_cleaner_page_is_anonymous(): void
    {
        $this->get(route('checklist.index'))->assertOk();
        $this->post(route('admin.sessions.store'), ['name' => 'Malam'])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('password');
    }

    public function test_non_current_completion_is_rejected_server_side(): void
    {
        Storage::fake('local');
        $task = $this->dailyTask('Old', app(OperationalDate::class)->today()->subDay()->toDateString());

        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$this->proof()],
        ])->assertForbidden();
        $this->assertFalse($task->refresh()->is_completed);
    }

    public function test_completion_fails_cleanly_when_watermarking_is_unavailable(): void
    {
        Storage::fake('local');
        $this->app->instance(EvidenceWatermarker::class, new class extends EvidenceWatermarker
        {
            public function watermark(UploadedFile $photo, string $text): array
            {
                throw ValidationException::withMessages([
                    'photos' => 'Pemprosesan watermark foto tidak tersedia. Sila aktifkan extension PHP GD dan EXIF di server.',
                ]);
            }
        });

        $task = $this->dailyTask();
        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$this->proof()],
        ])->assertSessionHasErrors('photos');

        $this->assertFalse($task->refresh()->is_completed);
        $this->assertSame(0, $task->evidence()->count());
    }

    public function test_completion_stores_hard_watermarked_image_when_gd_is_available(): void
    {
        $watermarker = app(EvidenceWatermarker::class);

        if (! $watermarker->isAvailable('image/png')) {
            $this->markTestSkipped('PHP GD with PNG support is required to verify image watermarking.');
        }

        Storage::fake('local');
        $task = $this->dailyTask();
        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$this->whitePngProof()],
        ])->assertRedirect(route('checklist.index', ['date' => $task->date->toDateString()]));

        $evidence = $task->evidence()->sole();
        $contents = Storage::disk('local')->get($evidence->path);
        $image = imagecreatefromstring($contents);
        $sample = imagecolorat($image, 6, 136);
        $red = ($sample >> 16) & 0xFF;
        $green = ($sample >> 8) & 0xFF;
        $blue = $sample & 0xFF;
        imagedestroy($image);

        $this->assertSame('image/png', $evidence->mime_type);
        $this->assertLessThan(250, max($red, $green, $blue));
    }

    public function test_completion_corrects_phone_jpeg_orientation_before_watermarking(): void
    {
        $watermarker = app(EvidenceWatermarker::class);

        if (! $watermarker->isAvailable('image/jpeg')) {
            $this->markTestSkipped('PHP GD with JPEG support and EXIF support is required to verify phone photo orientation.');
        }

        Storage::fake('local');
        $task = $this->dailyTask();
        $this->post(route('tasks.daily.complete', $task), [
            'date' => $task->date->toDateString(),
            'photos' => [$this->phoneJpegProof(6)],
        ])->assertRedirect(route('checklist.index', ['date' => $task->date->toDateString()]));

        $evidence = $task->evidence()->sole();
        $contents = Storage::disk('local')->get($evidence->path);
        $image = imagecreatefromstring($contents);
        $width = imagesx($image);
        $height = imagesy($image);
        $sample = imagecolorat($image, 10, $height - 10);
        $red = ($sample >> 16) & 0xFF;
        $green = ($sample >> 8) & 0xFF;
        $blue = $sample & 0xFF;
        imagedestroy($image);

        $this->assertSame('image/jpeg', $evidence->mime_type);
        $this->assertLessThan($height, $width, 'EXIF orientation 6 should be rotated into portrait dimensions.');
        $this->assertLessThan(250, max($red, $green, $blue), 'Watermark background should visibly darken the bottom-left pixels.');
    }

    public function test_dashboard_source_keeps_admin_english_and_cleaner_malay(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Dashboard.vue'));

        $this->assertStringNotContainsString('Penta'.'dbir', $source);
        $this->assertStringNotContainsString('penta'.'dbir', $source);
        $this->assertStringContainsString('Admin Access', $source);
        $this->assertStringContainsString('function openAdminLogin()', $source);
        $this->assertStringContainsString("document.getElementById('admin-password')?.focus()", $source);
        $this->assertStringContainsString('Dashboard', $source);
        $this->assertStringContainsString('Back to today', $source);
        $this->assertStringContainsString('Buka senarai hari ini', $source);
        $this->assertStringContainsString('Hantar bukti & tandakan selesai', $source);
        $this->assertStringContainsString('() => closeEvidence(true)', $source);
        $this->assertStringContainsString("{ key: 'tasks', label: 'Manage Tasks', icon: 'tasks' }", $source);
        $this->assertStringContainsString("{ key: 'collections', label: 'Rotations', icon: 'rotations' }", $source);
        $this->assertStringNotContainsString('Weekly Task Editor', $source);
        $this->assertStringNotContainsString('Daily Task Editor', $source);
        $this->assertStringContainsString('Rotation calendar', $source);
        $this->assertStringContainsString('trendRangeLabel()', $source);
        $this->assertStringNotContainsString('Latest five working days through today', $source);
        $this->assertStringContainsString('auditActorTone', $source);
        $this->assertStringContainsString('rotationCalendar', $source);
        $this->assertStringContainsString('collectionCalendarWeeks', $source);
        $this->assertStringContainsString('rotation-calendar-week-label', $source);
        $this->assertStringContainsString('collectionCalendarBandDays', $source);
        $this->assertStringContainsString('collectionCalendarBandHasInMonthDay', $source);
        $this->assertStringContainsString('rotation-calendar-band__segments', $source);
        $this->assertStringContainsString('rotation-calendar-band__segment', $source);
        $this->assertStringContainsString('collectionCalendarBandHasInMonthDay(week) && !day.inMonth', $source);
        $this->assertStringContainsString('Rotation: {{ shortCollectionName(collectionDisplayName(week.rotation)) }}', $source);
        $this->assertStringNotContainsString('Median task duration:', $source);
        $this->assertStringContainsString("adminIconPath('logout')", $source);
        $this->assertStringContainsString('maxFileMb }} MB setiap satu', $source);
    }

    private function taskSession(string $name = 'Pagi'): TaskSession
    {
        return TaskSession::query()->where('name', $name)->sole();
    }

    private function dailyTemplate(string $name, string $session = 'Pagi', float $credits = 1): TaskTemplate
    {
        $template = TaskTemplate::query()->create([
            'task_name' => $name,
            'task_session_id' => $this->taskSession($session)->id,
            'task_collection_id' => $this->defaultCollection()->id,
            'applies_to_all_collections' => false,
            'credit_hours' => $credits,
            'sort_order' => (int) TaskTemplate::query()->max('sort_order') + 1,
            'is_active' => true,
        ]);

        $template->taskCollections()->sync([$this->defaultCollection()->id]);

        return $template;
    }

    private function weeklyTemplate(string $name, int $dueWeekday, float $credits = 2): WeeklyTaskTemplate
    {
        $template = WeeklyTaskTemplate::query()->create([
            'task_name' => $name,
            'task_session_id' => $this->taskSession()->id,
            'task_collection_id' => $this->defaultCollection()->id,
            'applies_to_all_collections' => false,
            'due_weekday' => $dueWeekday,
            'credit_hours' => $credits,
            'sort_order' => (int) WeeklyTaskTemplate::query()->max('sort_order') + 1,
            'starts_on' => app(OperationalDate::class)->today()->startOfWeek()->toDateString(),
            'is_active' => true,
        ]);

        $template->taskCollections()->sync([$this->defaultCollection()->id]);

        return $template;
    }

    private function defaultCollection(): TaskCollection
    {
        return TaskCollection::query()->where('is_default', true)->sole();
    }

    private function dailyTask(string $name = 'Clean entrance glass', ?string $date = null): DailyChecklist
    {
        $template = $this->dailyTemplate($name);

        return DailyChecklist::query()->create([
            'date' => $date ?? app(OperationalDate::class)->today()->toDateString(),
            'task_template_id' => $template->id,
            'task_name' => $template->task_name,
            'task_session_id' => $template->task_session_id,
            'session_name' => $template->taskSession->name,
            'credit_hours' => $template->credit_hours,
            'is_completed' => false,
        ]);
    }

    private function proof(): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nXsAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('proof.png', $png);
    }

    private function whitePngProof(): UploadedFile
    {
        $image = imagecreatetruecolor(320, 160);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 319, 159, $white);
        ob_start();
        imagepng($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('proof.png', $contents);
    }

    private function phoneJpegProof(int $orientation): UploadedFile
    {
        $image = imagecreatetruecolor(120, 80);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 119, 79, $white);

        ob_start();
        imagejpeg($image, null, 90);
        $contents = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('proof.jpg', $this->addExifOrientation($contents, $orientation));
    }

    private function addExifOrientation(string $jpeg, int $orientation): string
    {
        $payload = "Exif\0\0"
            .'II'
            .pack('v', 42)
            .pack('V', 8)
            .pack('v', 1)
            .pack('v', 0x0112)
            .pack('v', 3)
            .pack('V', 1)
            .pack('v', $orientation)
            .pack('v', 0)
            .pack('V', 0);

        $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
    }

    private function fakeWatermarker(): void
    {
        $this->app->instance(EvidenceWatermarker::class, new class extends EvidenceWatermarker
        {
            public function watermark(UploadedFile $photo, string $text): array
            {
                $path = $photo->getRealPath();
                $contents = is_string($path) ? file_get_contents($path) : '';
                $mime = (string) $photo->getMimeType();

                return [
                    'contents' => is_string($contents) ? $contents : '',
                    'mime_type' => $mime,
                    'extension' => match ($mime) {
                        'image/jpeg' => 'jpg',
                        'image/webp' => 'webp',
                        default => 'png',
                    },
                    'size_bytes' => is_string($contents) ? strlen($contents) : 0,
                ];
            }
        });
    }

    private function loginAdmin(): void
    {
        $this->post(route('admin.login'), ['password' => 'test-master-password'])
            ->assertRedirect(route('admin.index'));
    }
}
