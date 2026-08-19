<?php

namespace Tests\Feature;

use App\Models\DailyChecklist;
use App\Models\TaskCollection;
use App\Models\TaskSession;
use App\Models\TaskTemplate;
use App\Services\ChecklistMaterializer;
use App\Services\OperationalDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskDaySchedulingTest extends TestCase
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
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 09:00:00', 'Asia/Kuala_Lumpur'));
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_create_task_with_specific_days_of_week(): void
    {
        $this->loginAdmin();
        $session = TaskSession::query()->first();
        $collection = TaskCollection::query()->where('is_default', true)->first();

        $response = $this->post(route('admin.templates.store'), [
            'task_name' => 'Mop hallway on Mon & Wed',
            'task_session_id' => $session->id,
            'days_of_week' => [1, 3], // Monday & Wednesday
            'finish_time' => '10:30',
            'applies_to_all_collections' => true,
        ]);

        $response->assertRedirect(route('admin.index'));

        $template = TaskTemplate::query()->where('task_name', 'Mop hallway on Mon & Wed')->first();
        $this->assertNotNull($template);
        $this->assertSame([1, 3], $template->days_of_week);
    }

    public function test_creating_task_requires_at_least_one_day_of_week(): void
    {
        $this->loginAdmin();
        $session = TaskSession::query()->first();

        $response = $this->post(route('admin.templates.store'), [
            'task_name' => 'Invalid no days task',
            'task_session_id' => $session->id,
            'days_of_week' => [],
            'finish_time' => '10:30',
            'applies_to_all_collections' => true,
        ]);

        $response->assertSessionHasErrors('days_of_week');
    }

    public function test_creating_task_rejects_invalid_days_of_week(): void
    {
        $this->loginAdmin();
        $session = TaskSession::query()->first();

        $response = $this->post(route('admin.templates.store'), [
            'task_name' => 'Invalid weekend day',
            'task_session_id' => $session->id,
            'days_of_week' => [0, 6, 7],
            'finish_time' => '10:30',
            'applies_to_all_collections' => true,
        ]);

        $response->assertSessionHasErrors('days_of_week.0');
    }

    public function test_checklist_only_materializes_task_on_its_scheduled_days(): void
    {
        $session = TaskSession::query()->first();
        $collection = TaskCollection::query()->where('is_default', true)->first();

        // Create a task template scheduled only for Monday (1) and Wednesday (3)
        $template = TaskTemplate::query()->create([
            'task_name' => 'Monday and Wednesday Only',
            'task_session_id' => $session->id,
            'days_of_week' => [1, 3],
            'task_collection_id' => $collection->id,
            'applies_to_all_collections' => true,
            'finish_time' => '10:00:00',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $materializer = app(ChecklistMaterializer::class);

        // 2026-07-13 is Monday (ISO weekday 1)
        $monday = CarbonImmutable::parse('2026-07-13', 'Asia/Kuala_Lumpur');
        $mondayTasks = $materializer->forDate($monday);
        $this->assertTrue($mondayTasks->contains('task_template_id', $template->id));

        // 2026-07-14 is Tuesday (ISO weekday 2)
        $tuesday = CarbonImmutable::parse('2026-07-14', 'Asia/Kuala_Lumpur');
        $tuesdayTasks = $materializer->forDate($tuesday);
        $this->assertFalse($tuesdayTasks->contains('task_template_id', $template->id));

        // 2026-07-15 is Wednesday (ISO weekday 3)
        $wednesday = CarbonImmutable::parse('2026-07-15', 'Asia/Kuala_Lumpur');
        $wednesdayTasks = $materializer->forDate($wednesday);
        $this->assertTrue($wednesdayTasks->contains('task_template_id', $template->id));

        // 2026-07-16 is Thursday (ISO weekday 4)
        $thursday = CarbonImmutable::parse('2026-07-16', 'Asia/Kuala_Lumpur');
        $thursdayTasks = $materializer->forDate($thursday);
        $this->assertFalse($thursdayTasks->contains('task_template_id', $template->id));

        // 2026-07-17 is Friday (ISO weekday 5)
        $friday = CarbonImmutable::parse('2026-07-17', 'Asia/Kuala_Lumpur');
        $fridayTasks = $materializer->forDate($friday);
        $this->assertFalse($fridayTasks->contains('task_template_id', $template->id));
    }

    public function test_updating_task_days_syncs_materialized_checklists(): void
    {
        $this->loginAdmin();
        $session = TaskSession::query()->first();
        $collection = TaskCollection::query()->where('is_default', true)->first();

        // Start with Monday & Wednesday
        $template = TaskTemplate::query()->create([
            'task_name' => 'Sync Test Task',
            'task_session_id' => $session->id,
            'days_of_week' => [1, 3],
            'task_collection_id' => $collection->id,
            'applies_to_all_collections' => true,
            'finish_time' => '10:00:00',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $materializer = app(ChecklistMaterializer::class);
        $today = CarbonImmutable::parse('2026-07-15', 'Asia/Kuala_Lumpur'); // Wednesday

        // Materialize today (Wednesday)
        $wednesdayTasks = $materializer->forDate($today);
        $this->assertTrue($wednesdayTasks->contains('task_template_id', $template->id));

        // Update template to run only on Monday and Tuesday [1, 2] (removing Wednesday)
        $response = $this->patch(route('admin.templates.update', $template), [
            'task_name' => 'Sync Test Task',
            'task_session_id' => $session->id,
            'days_of_week' => [1, 2], // Mon & Tue
            'finish_time' => '10:00',
            'applies_to_all_collections' => true,
        ]);

        $response->assertRedirect(route('admin.index'));

        // Refresh template and check DB
        $template->refresh();
        $this->assertSame([1, 2], $template->days_of_week);

        // Since Wednesday is today and the task was incomplete, it should be removed from Wednesday
        $this->assertDatabaseMissing('daily_checklists', [
            'task_template_id' => $template->id,
            'date' => $today->toDateString(),
        ]);
    }

    public function test_admin_dashboard_props_include_days_of_week(): void
    {
        $this->loginAdmin();
        $session = TaskSession::query()->first();
        $collection = TaskCollection::query()->where('is_default', true)->first();

        $template = TaskTemplate::query()->create([
            'task_name' => 'Props Test Task',
            'task_session_id' => $session->id,
            'days_of_week' => [2, 4], // Tue & Thu
            'task_collection_id' => $collection->id,
            'applies_to_all_collections' => true,
            'finish_time' => '10:00:00',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('templates', fn (Assert $templates) => $templates
                    ->where('0.daysOfWeek', [2, 4])
                    ->etc()
                )
            );
    }

    private function loginAdmin(): void
    {
        $password = 'test-master-password';
        $fingerprint = hash_hmac('sha256', $password, (string) config('app.key', ''));
        $key = config('checklist.admin_session_key', 'checklist.admin');

        $this->withSession([
            $key.'.fingerprint' => $fingerprint,
            $key.'.authenticated_at' => time(),
        ]);
    }
}
