<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyAndProcessLiveWebhookJob;
use App\Models\Officer;
use App\Services\YouTubeWebSubService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class YouTubeWebSubController extends Controller
{
    public function verify(Request $request, Officer $officer, YouTubeWebSubService $webSub): Response
    {
        $subscription = $officer->webSubSubscription;
        $token = (string) $request->query('token', '');

        if (! $subscription || $token === '' || ! hash_equals($subscription->callback_token, $token)) {
            return response('Forbidden', 403);
        }

        $mode = (string) ($request->query('hub_mode') ?? $request->query('hub.mode') ?? '');
        $topic = (string) ($request->query('hub_topic') ?? $request->query('hub.topic') ?? '');
        $challenge = (string) ($request->query('hub_challenge') ?? $request->query('hub.challenge') ?? '');
        $leaseSeconds = (int) ($request->query('hub_lease_seconds') ?? $request->query('hub.lease_seconds') ?? $subscription->lease_seconds ?? YouTubeWebSubService::DEFAULT_LEASE_SECONDS);

        if (! in_array($mode, ['subscribe', 'unsubscribe'], true) || $challenge === '') {
            return response('Bad Request', 400);
        }

        $expectedTopic = $webSub->topicUrl($subscription->channel_id);
        if ($topic !== '' && $topic !== $expectedTopic) {
            return response('Topic Mismatch', 400);
        }

        if ($mode === 'subscribe') {
            $subscription->update([
                'status' => 'active',
                'lease_seconds' => $leaseSeconds > 0 ? $leaseSeconds : YouTubeWebSubService::DEFAULT_LEASE_SECONDS,
                'expires_at' => now()->addSeconds($leaseSeconds > 0 ? $leaseSeconds : YouTubeWebSubService::DEFAULT_LEASE_SECONDS),
                'last_verified_at' => now(),
                'last_error' => null,
            ]);
        } else {
            $subscription->update([
                'status' => 'expired',
                'expires_at' => now(),
            ]);
        }

        return response($challenge, 200, ['Content-Type' => 'text/plain']);
    }

    public function handle(Request $request, Officer $officer, YouTubeWebSubService $webSub): Response
    {
        $subscription = $officer->webSubSubscription;
        $token = (string) $request->query('token', '');

        if (! $subscription || $token === '' || ! hash_equals($subscription->callback_token, $token)) {
            return response('Forbidden', 403);
        }

        $rawBody = $request->getContent();
        $secret = config('services.youtube_websub.secret');

        if (is_string($secret) && $secret !== '') {
            $signature = (string) $request->header('X-Hub-Signature', '');
            if (! $this->isValidSignature($signature, $rawBody, $secret)) {
                return response('Invalid Signature', 403);
            }
        }

        $subscription->update(['last_notification_at' => now()]);

        $entries = $webSub->parseAtomFeed($rawBody);
        foreach ($entries as $entry) {
            if ($entry['channel_id'] !== null && $entry['channel_id'] !== $subscription->channel_id) {
                continue;
            }

            VerifyAndProcessLiveWebhookJob::dispatch(
                $officer->id,
                $entry['video_id'],
                $entry['title'],
            );
        }

        return response()->noContent();
    }

    private function isValidSignature(string $signatureHeader, string $payload, string $secret): bool
    {
        if (! str_contains($signatureHeader, '=')) {
            return false;
        }

        [$algo, $hash] = explode('=', $signatureHeader, 2);
        if (! in_array($algo, ['sha1', 'sha256'], true)) {
            return false;
        }

        $expected = hash_hmac($algo, $payload, $secret);

        return hash_equals($expected, $hash);
    }
}
