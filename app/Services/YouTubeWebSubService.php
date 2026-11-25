<?php

namespace App\Services;

use App\Models\Officer;
use App\Models\YouTubeWebSubSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class YouTubeWebSubService
{
    public const HUB_URL = 'https://pubsubhubbub.appspot.com/subscribe';
    public const TOPIC_BASE = 'https://www.youtube.com/xml/feeds/videos.xml?channel_id=';
    public const DEFAULT_LEASE_SECONDS = 432000; // 5 days

    public function isEligibleChannel(?string $channelId): bool
    {
        $clean = trim((string) $channelId);

        if ($clean === '' || str_starts_with($clean, 'UC_')) {
            return false;
        }

        return (bool) preg_match('/^UC[A-Za-z0-9_-]{10,}$/', $clean);
    }

    public function topicUrl(string $channelId): string
    {
        return self::TOPIC_BASE . urlencode(trim($channelId));
    }

    public function callbackUrl(Officer $officer, string $token): string
    {
        return route('webhooks.youtube.websub.verify', [
            'officer' => $officer->id,
            'token' => $token,
        ]);
    }

    public function subscribe(Officer $officer, ?int $leaseSeconds = null): ?YouTubeWebSubSubscription
    {
        if (! $this->isEligibleChannel($officer->channel_id)) {
            return null;
        }

        $channelId = trim((string) $officer->channel_id);
        $lease = $leaseSeconds ?? (int) config('services.youtube_websub.lease_seconds', self::DEFAULT_LEASE_SECONDS);

        $subscription = YouTubeWebSubSubscription::firstOrNew(['officer_id' => $officer->id]);
        if (! $subscription->callback_token || $subscription->channel_id !== $channelId) {
            $subscription->callback_token = Str::random(64);
        }
        $subscription->channel_id = $channelId;
        $subscription->lease_seconds = $lease;
        $subscription->status = 'pending';
        $subscription->save();

        $payload = [
            'hub.callback' => $this->callbackUrl($officer, $subscription->callback_token),
            'hub.topic' => $this->topicUrl($channelId),
            'hub.verify' => 'async',
            'hub.mode' => 'subscribe',
            'hub.lease_seconds' => $lease,
        ];

        $secret = config('services.youtube_websub.secret');
        if (is_string($secret) && $secret !== '') {
            $payload['hub.secret'] = $secret;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(self::HUB_URL, $payload);

            if ($response->successful()) {
                $subscription->update([
                    'status' => 'pending',
                    'last_error' => null,
                ]);
            } else {
                $subscription->update([
                    'status' => 'failed',
                    'last_error' => "HTTP {$response->status()}: " . Str::limit($response->body(), 300),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('YouTube WebSub subscribe request failed', [
                'officer_id' => $officer->id,
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);

            $subscription->update([
                'status' => 'failed',
                'last_error' => Str::limit($e->getMessage(), 300),
            ]);
        }

        return $subscription->fresh();
    }

    public function unsubscribe(Officer $officer): bool
    {
        $subscription = $officer->webSubSubscription;
        if (! $subscription) {
            return false;
        }

        $payload = [
            'hub.callback' => $this->callbackUrl($officer, $subscription->callback_token),
            'hub.topic' => $this->topicUrl($subscription->channel_id),
            'hub.verify' => 'async',
            'hub.mode' => 'unsubscribe',
        ];

        try {
            Http::asForm()->timeout(10)->post(self::HUB_URL, $payload);
        } catch (\Throwable $e) {
            Log::warning('YouTube WebSub unsubscribe request failed', [
                'officer_id' => $officer->id,
                'error' => $e->getMessage(),
            ]);
        }

        $subscription->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);

        return true;
    }

    /**
     * Subscribe new officers with UC channel_id and renew leases expiring within 24h.
     *
     * @return array{total: int, requested: int, failed: int}
     */
    public function syncDueSubscriptions(): array
    {
        $officers = Officer::roster()
            ->whereNotNull('channel_id')
            ->with('webSubSubscription')
            ->get();

        $total = 0;
        $requested = 0;
        $failed = 0;
        $renewThreshold = now()->addDay();

        foreach ($officers as $officer) {
            if (! $this->isEligibleChannel($officer->channel_id)) {
                continue;
            }

            $sub = $officer->webSubSubscription;
            $needsSync = ! $sub
                || $sub->channel_id !== trim((string) $officer->channel_id)
                || in_array($sub->status, ['expired', 'failed'], true)
                || $sub->expires_at === null
                || $sub->expires_at->lessThan($renewThreshold);

            if (! $needsSync) {
                continue;
            }

            $total++;
            $result = $this->subscribe($officer);
            if ($result && $result->status !== 'failed') {
                $requested++;
            } else {
                $failed++;
            }
        }

        return [
            'total' => $total,
            'requested' => $requested,
            'failed' => $failed,
        ];
    }

    /**
     * Parse YouTube PubSubHubbub Atom XML payload into normalized video entries.
     *
     * @return array<int, array{video_id: string, channel_id: ?string, title: ?string, published: ?string}>
     */
    public function parseAtomFeed(string $xmlContent): array
    {
        $trimmed = trim($xmlContent);
        if ($trimmed === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($trimmed);
            if ($xml === false) {
                return [];
            }

            $entries = [];
            foreach ($xml->entry ?? [] as $entry) {
                $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
                $videoId = trim((string) ($yt->videoId ?? ''));
                $channelId = trim((string) ($yt->channelId ?? ''));

                if ($videoId === '' && isset($entry->children('yt', true)->videoId)) {
                    $videoId = trim((string) $entry->children('yt', true)->videoId);
                    $channelId = trim((string) $entry->children('yt', true)->channelId);
                }

                if ($videoId === '' || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
                    continue;
                }

                $entries[] = [
                    'video_id' => $videoId,
                    'channel_id' => $channelId !== '' ? $channelId : null,
                    'title' => trim((string) ($entry->title ?? '')) ?: null,
                    'published' => trim((string) ($entry->published ?? '')) ?: null,
                ];
            }

            return $entries;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
