<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\MasterAdminSession;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Throwable;

class PushSubscriptionController extends Controller
{
    public function publicKey(WebPushService $webPush): JsonResponse
    {
        return response()->json([
            'publicKey' => $webPush->getPublicKey(),
            'configured' => $webPush->isConfigured(),
        ]);
    }

    public function subscribe(Request $request, MasterAdminSession $adminSession): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:1000'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:500'],
            'keys.auth' => ['required', 'string', 'max:500'],
            'content_encoding' => ['nullable', 'string', 'max:32'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'cleaner'])],
        ]);

        $isAdmin = $adminSession->isAuthenticated($request);
        $role = $validated['role'] ?? ($isAdmin ? 'admin' : 'cleaner');

        // Security check: only allow 'admin' subscription role if actually authenticated as admin
        if ($role === 'admin' && ! $isAdmin) {
            $role = 'cleaner';
        }

        try {
            if (! Schema::hasTable('push_subscriptions')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Push notification table not migrated yet',
                ], 503);
            }

            $endpointHash = hash('sha256', $validated['endpoint']);

            $subscription = PushSubscription::query()->updateOrCreate(
                ['endpoint_hash' => $endpointHash],
                [
                    'endpoint' => $validated['endpoint'],
                    'public_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                    'content_encoding' => $validated['content_encoding'] ?? 'aes128gcm',
                    'role' => $role,
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'last_active_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'role' => $subscription->role,
            ]);
        } catch (Throwable $e) {
            Log::warning('Push subscribe error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Subscription failed',
            ], 500);
        }
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000'],
        ]);

        try {
            if (Schema::hasTable('push_subscriptions')) {
                $endpointHash = hash('sha256', $validated['endpoint']);

                PushSubscription::query()
                    ->where('endpoint_hash', $endpointHash)
                    ->delete();
            }
        } catch (Throwable $e) {
            Log::warning('Push unsubscribe error: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function test(Request $request, WebPushService $webPush): JsonResponse
    {
        $count = $webPush->notifyAdmins(
            '🔔 Ujian Notifikasi Push',
            'Sistem notifikasi FF Spotless berfungsi dengan jayanya pada peranti anda!',
            route('admin.index')
        );

        return response()->json([
            'success' => true,
            'sent' => $count,
        ]);
    }
}
