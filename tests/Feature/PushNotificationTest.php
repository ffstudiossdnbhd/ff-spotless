<?php

namespace Tests\Feature;

use App\Models\DailyChecklist;
use App\Models\PushSubscription;
use App\Models\TaskSession;
use App\Models\TaskTemplate;
use App\Services\ChecklistMaterializer;
use App\Services\OperationalDate;
use App\Services\TaskCompletionService;
use App\Services\TaskReopenService;
use App\Services\WebPushService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('checklist.timezone', 'Asia/Kuala_Lumpur');
        config()->set('checklist.admin_password', 'test-admin-secret');
        config()->set('webpush.vapid.public_key', 'test-vapid-public-key');
        config()->set('webpush.vapid.private_key', 'test-vapid-private-key');
        config()->set('webpush.vapid.subject', 'mailto:admin@ffspotless.test');

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 09:00:00', 'Asia/Kuala_Lumpur'));
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_public_key_endpoint_returns_vapid_key(): void
    {
        $response = $this->getJson(route('push.public-key'));

        $response->assertOk()
            ->assertJson([
                'publicKey' => 'test-vapid-public-key',
                'configured' => true,
            ]);
    }

    public function test_cleaner_can_subscribe_to_push_notifications(): void
    {
        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-cleaner-1',
            'keys' => [
                'p256dh' => 'BNcRdreALRF8M+Ut5RThKgwhQgTqdMGoqVbFLQgID3VJUK2D1mRV_3Jg',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'role' => 'cleaner',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'role' => 'cleaner']);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/test-endpoint-cleaner-1'),
            'role' => 'cleaner',
        ]);
    }

    public function test_unauthenticated_user_cannot_claim_admin_role_subscription(): void
    {
        // When not authenticated as admin, requesting 'admin' role automatically falls back to 'cleaner'
        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-cleaner-2',
            'keys' => [
                'p256dh' => 'BNcRdreALRF8M+Ut5RThKgwhQgTqdMGoqVbFLQgID3VJUK2D1mRV_3Jg',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'role' => 'admin',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'role' => 'cleaner']);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/test-endpoint-cleaner-2'),
            'role' => 'cleaner',
        ]);
    }

    public function test_admin_can_subscribe_as_admin_role_when_authenticated(): void
    {
        $loginResponse = $this->post(route('admin.login'), [
            'password' => 'test-admin-secret',
        ]);
        $loginResponse->assertRedirect(route('admin.index'));

        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-admin-1',
            'keys' => [
                'p256dh' => 'BNcRdreALRF8M+Ut5RThKgwhQgTqdMGoqVbFLQgID3VJUK2D1mRV_3Jg',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
            'role' => 'admin',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'role' => 'admin']);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', 'https://fcm.googleapis.com/fcm/send/test-endpoint-admin-1'),
            'role' => 'admin',
        ]);
    }

    public function test_anonymous_cleaner_sync_does_not_demote_an_existing_admin_subscription(): void
    {
        $endpoint = 'https://fcm.googleapis.com/fcm/send/existing-admin-endpoint';

        PushSubscription::query()->create([
            'endpoint' => $endpoint,
            'public_key' => 'existing-public-key',
            'auth_token' => 'existing-auth-token',
            'role' => 'admin',
        ]);

        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'refreshed-public-key',
                'auth' => 'refreshed-auth-token',
            ],
            'role' => 'cleaner',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'role' => 'admin']);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => 'refreshed-public-key',
            'auth_token' => 'refreshed-auth-token',
            'role' => 'admin',
        ]);
    }

    public function test_user_can_unsubscribe(): void
    {
        PushSubscription::query()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-to-remove',
            'public_key' => 'pubkey',
            'auth_token' => 'auth',
            'role' => 'cleaner',
        ]);

        $this->assertDatabaseCount('push_subscriptions', 1);

        $response = $this->postJson(route('push.unsubscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-to-remove',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_admin_push_test_reports_when_no_admin_device_is_subscribed(): void
    {
        $this->post(route('admin.login'), [
            'password' => 'test-admin-secret',
        ])->assertRedirect(route('admin.index'));

        $response = $this->postJson(route('admin.push.test'));

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'sent' => 0,
            ]);
    }

    public function test_admin_push_test_reports_provider_delivery_failure(): void
    {
        $this->post(route('admin.login'), [
            'password' => 'test-admin-secret',
        ])->assertRedirect(route('admin.index'));

        PushSubscription::query()->create([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/admin-delivery-failure',
            'public_key' => 'public-key',
            'auth_token' => 'auth-token',
            'role' => 'admin',
        ]);

        $mockPush = Mockery::mock(WebPushService::class);
        $mockPush->shouldReceive('isConfigured')->once()->andReturnTrue();
        $mockPush->shouldReceive('notifyAdmins')->once()->andReturn(0);
        $this->app->instance(WebPushService::class, $mockPush);

        $response = $this->postJson(route('admin.push.test'));

        $response->assertStatus(502)
            ->assertJson([
                'success' => false,
                'sent' => 0,
            ]);
    }

    public function test_reopening_task_triggers_push_notification_to_cleaners(): void
    {
        $mockPush = Mockery::mock(WebPushService::class);
        $mockPush->shouldReceive('notifyCleaners')
            ->once()
            ->with(
                '⚠️ Tugasan Dibuka Semula',
                Mockery::pattern('/Tugasan "Kemas Bilik" telah dibuka semula. Sebab: Perlu dilap sekali lagi/'),
                Mockery::pattern('/date=2026-07-15/'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $this->app->instance(WebPushService::class, $mockPush);

        $session = TaskSession::query()->first();
        $collection = \App\Models\TaskCollection::query()->where('is_default', true)->first();

        $template = TaskTemplate::query()->create([
            'task_name' => 'Kemas Bilik',
            'task_session_id' => $session->id,
            'task_collection_id' => $collection->id,
            'applies_to_all_collections' => true,
            'finish_time' => '12:00:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $task = DailyChecklist::query()->create([
            'date' => '2026-07-15',
            'task_template_id' => $template->id,
            'task_name' => 'Kemas Bilik',
            'session_name' => $session->name,
            'task_session_id' => $session->id,
            'finish_time' => '12:00:00',
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $reopenService = app(TaskReopenService::class);
        $reopenService->reopenDaily($task, 'Perlu dilap sekali lagi');

        $this->assertFalse($task->fresh()->is_completed);
    }

    public function test_completing_final_task_triggers_push_notification_to_admins(): void
    {
        $mockPush = Mockery::mock(WebPushService::class);
        $mockPush->shouldReceive('notifyAdmins')
            ->once()
            ->with(
                '🎉 Senarai Semak Selesai!',
                Mockery::pattern('/Semua tugasan untuk tarikh 15\/07\/2026 telah diselesaikan/'),
                Mockery::pattern('/date=2026-07-15/'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $this->app->instance(WebPushService::class, $mockPush);

        $session = TaskSession::query()->first();
        $collection = \App\Models\TaskCollection::query()->where('is_default', true)->first();

        $template = TaskTemplate::query()->create([
            'task_name' => 'Sapu Lantai',
            'task_session_id' => $session->id,
            'task_collection_id' => $collection->id,
            'applies_to_all_collections' => true,
            'finish_time' => '12:00:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $dates = app(OperationalDate::class);
        $materializer = app(ChecklistMaterializer::class);
        $tasks = $materializer->forDate($dates->today());

        $dailyTask = $tasks->firstWhere('task_template_id', $template->id);
        $this->assertNotNull($dailyTask);

        $completionService = app(TaskCompletionService::class);
        $completionService->completeDaily($dailyTask, '2026-07-15', [], 'Siap semua');

        $this->assertTrue($dailyTask->fresh()->is_completed);
    }
}
