<?php

namespace App\Services;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function isConfigured(): bool
    {
        $publicKey = (string) config('services.webpush.public_key', '');
        $privateKey = (string) config('services.webpush.private_key', '');

        return $publicKey !== '' && $privateKey !== '';
    }

    public function publicKey(): ?string
    {
        $key = trim((string) config('services.webpush.public_key', ''));

        return $key !== '' ? $key : null;
    }

    /**
     * @param  iterable<int, PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $payload
     * @return array{sent: int, expired: int, failed: int}
     */
    public function sendToSubscriptions(iterable $subscriptions, array $payload): array
    {
        $items = collect($subscriptions)->values();
        if ($items->isEmpty() || ! $this->isConfigured()) {
            return ['sent' => 0, 'expired' => 0, 'failed' => 0];
        }

        $webPush = $this->makeWebPushClient();
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $byEndpoint = [];

        foreach ($items as $sub) {
            $byEndpoint[$sub->endpoint] = $sub;

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
                ]),
                $jsonPayload !== false ? $jsonPayload : '{}',
            );
        }

        $sent = 0;
        $expired = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();
            $model = $byEndpoint[$endpoint] ?? null;

            if ($report->isSuccess()) {
                $sent++;
                if ($model) {
                    $model->forceFill(['last_used_at' => now()])->save();
                }

                continue;
            }

            $statusCode = $report->getResponse()?->getStatusCode();
            if ($report->isSubscriptionExpired() || in_array($statusCode, [404, 410], true)) {
                $expired++;
                if ($model) {
                    $model->delete();
                } else {
                    PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))->delete();
                }

                continue;
            }

            $failed++;
            Log::warning('Web Push delivery failed', [
                'endpoint_hash' => PushSubscription::hashEndpoint($endpoint),
                'status' => $statusCode,
                'reason' => $report->getReason(),
            ]);
        }

        return [
            'sent' => $sent,
            'expired' => $expired,
            'failed' => $failed,
        ];
    }

    /**
     * @return array{sent: int, expired: int, failed: int}
     */
    public function sendOfficerLiveNotification(User $user, Officer $officer, ActiveStream $stream): array
    {
        $officerLabel = trim(($officer->callsign ? "[{$officer->callsign}] " : '') . $officer->officer_name);

        return $this->sendToSubscriptions($user->pushSubscriptions, [
            'title' => "{$officerLabel} sedang LIVE Patroli!",
            'body' => $stream->title ?: 'Siaran langsung patroli kepolisian baru saja dimulai.',
            'icon' => $officer->public_avatar_url ?: '/icons/icon-192x192.png',
            'badge' => '/icons/icon-192x192.png',
            'tag' => "officer-live-{$officer->id}-{$stream->video_id}",
            'data' => [
                'url' => "/watch/{$stream->video_id}",
                'officer_id' => $officer->id,
                'video_id' => $stream->video_id,
            ],
        ]);
    }

    /**
     * @return array{sent: int, expired: int, failed: int}
     */
    public function sendTestNotification(User $user): array
    {
        return $this->sendToSubscriptions($user->pushSubscriptions, [
            'title' => 'Notifikasi Live Patroli Aktif',
            'body' => 'Perangkat ini siap menerima peringatan real-time saat perwira yang kamu ikuti mulai live.',
            'icon' => '/icons/icon-192x192.png',
            'badge' => '/icons/icon-192x192.png',
            'tag' => 'live-notif-test',
            'data' => [
                'url' => '/dashboard',
            ],
        ]);
    }

    protected function makeWebPushClient(): WebPush
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('services.webpush.subject', config('app.url', 'mailto:admin@example.com')),
                'publicKey' => (string) config('services.webpush.public_key'),
                'privateKey' => (string) config('services.webpush.private_key'),
            ],
        ]);

        $webPush->setReuseVAPIDHeaders(true);

        return $webPush;
    }
}
