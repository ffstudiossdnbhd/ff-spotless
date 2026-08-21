<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    private ?string $publicKey;
    private ?string $privateKey;
    private string $subject;

    public function __construct()
    {
        $this->publicKey = config('webpush.vapid.public_key') ?: env('VAPID_PUBLIC_KEY');
        $this->privateKey = config('webpush.vapid.private_key') ?: env('VAPID_PRIVATE_KEY');
        $this->subject = config('webpush.vapid.subject') ?: env('VAPID_SUBJECT', 'mailto:admin@ffspotless.local');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->publicKey) && ! empty($this->privateKey);
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function notifyAdmins(string $title, string $body, string $url, array $options = []): int
    {
        try {
            if (! Schema::hasTable('push_subscriptions')) {
                return 0;
            }

            $subscriptions = PushSubscription::query()->admin()->get();

            return $this->sendToSubscriptions($subscriptions, $title, $body, $url, $options);
        } catch (Throwable $e) {
            Log::warning('WebPush notifyAdmins failed: '.$e->getMessage());

            return 0;
        }
    }

    public function notifyCleaners(string $title, string $body, string $url, array $options = []): int
    {
        try {
            if (! Schema::hasTable('push_subscriptions')) {
                return 0;
            }

            $subscriptions = PushSubscription::query()->cleaner()->get();

            return $this->sendToSubscriptions($subscriptions, $title, $body, $url, $options);
        } catch (Throwable $e) {
            Log::warning('WebPush notifyCleaners failed: '.$e->getMessage());

            return 0;
        }
    }

    public function notifyAll(string $title, string $body, string $url, array $options = []): int
    {
        try {
            if (! Schema::hasTable('push_subscriptions')) {
                return 0;
            }

            $subscriptions = PushSubscription::query()->get();

            return $this->sendToSubscriptions($subscriptions, $title, $body, $url, $options);
        } catch (Throwable $e) {
            Log::warning('WebPush notifyAll failed: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     */
    public function sendToSubscriptions(
        Collection $subscriptions,
        string $title,
        string $body,
        string $url,
        array $options = []
    ): int {
        if ($subscriptions->isEmpty() || ! $this->isConfigured()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $options['icon'] ?? '/icons/ff-spotless-icon.svg',
            'badge' => $options['badge'] ?? '/icons/ff-spotless-icon.svg',
            'tag' => $options['tag'] ?? 'ffspotless-'.uniqid('', true),
            'vibrate' => [200, 100, 200],
            'data' => [
                'url' => $url,
                ...($options['data'] ?? []),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! class_exists(WebPush::class)) {
            Log::warning('WebPush class not found. Push notifications cannot be sent.');

            return 0;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject' => $this->subject,
                    'publicKey' => $this->publicKey,
                    'privateKey' => $this->privateKey,
                ],
            ];

            $webPush = new WebPush($auth, [
                'automatic_padding' => config('webpush.automatic_padding', 0),
                'timeout' => config('webpush.timeout', 15),
            ]);

            $subscriptionMap = [];
            foreach ($subscriptions as $sub) {
                $webPushSub = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
                ]);

                $webPush->queueNotification($webPushSub, $payload);
                $subscriptionMap[$sub->endpoint] = $sub;
            }

            $successCount = 0;
            $expiredEndpoints = [];

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $successCount++;
                } else {
                    Log::warning('WebPush delivery failed', [
                        'endpoint' => substr($endpoint, 0, 50).'...',
                        'reason' => $report->getReason(),
                        'response' => $report->getResponse()?->getStatusCode(),
                    ]);

                    if ($report->isSubscriptionExpired()) {
                        $expiredEndpoints[] = $endpoint;
                    }
                }
            }

            if (! empty($expiredEndpoints)) {
                $hashes = array_map(fn ($ep) => hash('sha256', $ep), $expiredEndpoints);
                PushSubscription::query()->whereIn('endpoint_hash', $hashes)->delete();
            }

            return $successCount;
        } catch (Throwable $e) {
            Log::error('WebPush notification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }
}
