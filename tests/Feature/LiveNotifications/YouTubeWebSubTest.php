<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\VerifyAndProcessLiveWebhookJob;
use App\Models\Officer;
use App\Models\YouTubeWebSubSubscription;
use App\Services\YouTubeWebSubService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class YouTubeWebSubTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'channel_id' => 'UC1234567890123456789012',
            'handle' => '@websubofficer',
            'streamer_name' => 'Streamer WebSub',
            'officer_name' => 'Officer WebSub',
            'callsign' => '2-B-10',
            'department' => 'LSPD',
            'rank' => 'Officer',
            'is_active' => true,
            'is_listed' => true,
        ], $overrides));
    }

    public function test_websub_sync_subscribes_eligible_officers_and_skips_ineligible(): void
    {
        Http::fake([
            YouTubeWebSubService::HUB_URL => Http::response('', 202),
        ]);

        $eligible = $this->createOfficer();
        $placeholder = $this->createOfficer([
            'channel_id' => 'UC_placeholder_123',
            'callsign' => '2-B-11',
        ]);
        $unlisted = $this->createOfficer([
            'channel_id' => 'UC9876543210987654321098',
            'callsign' => '2-B-12',
            'is_listed' => false,
        ]);

        $this->artisan('websub:sync')->assertExitCode(0);

        $this->assertDatabaseHas('youtube_websub_subscriptions', [
            'officer_id' => $eligible->id,
            'channel_id' => $eligible->channel_id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('youtube_websub_subscriptions', [
            'officer_id' => $placeholder->id,
        ]);
        $this->assertDatabaseMissing('youtube_websub_subscriptions', [
            'officer_id' => $unlisted->id,
        ]);

        Http::assertSentCount(1);
    }

    public function test_websub_verification_challenge_activates_lease_and_rejects_invalid_token(): void
    {
        $officer = $this->createOfficer();
        $sub = YouTubeWebSubSubscription::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'callback_token' => 'secret-token-123',
            'status' => 'pending',
        ]);

        $topic = urlencode("https://www.youtube.com/xml/feeds/videos.xml?channel_id={$officer->channel_id}");

        $this->get("/webhooks/youtube/websub/{$officer->id}?token=wrong&hub.mode=subscribe&hub.topic={$topic}&hub.challenge=abc123")
            ->assertStatus(403);

        $this->get("/webhooks/youtube/websub/{$officer->id}?token=secret-token-123&hub.mode=subscribe&hub.topic={$topic}&hub.challenge=challenge-xyz&hub.lease_seconds=432000")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSeeText('challenge-xyz');

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->expires_at);
        $this->assertNotNull($sub->last_verified_at);
    }

    public function test_websub_post_dispatches_verification_job_and_ignores_deleted_entries(): void
    {
        Queue::fake();

        $officer = $this->createOfficer();
        YouTubeWebSubSubscription::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'callback_token' => 'valid-token-456',
            'status' => 'active',
        ]);

        $atomXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns="http://www.w3.org/2005/Atom">
  <entry>
    <id>yt:video:dQw4w9WgXcQ</id>
    <yt:videoId>dQw4w9WgXcQ</yt:videoId>
    <yt:channelId>{$officer->channel_id}</yt:channelId>
    <title>Patroli LSPD 10-8 #imepolice</title>
    <published>2026-11-25T10:00:00+00:00</published>
  </entry>
</feed>
XML;

        $this->call('POST', "/webhooks/youtube/websub/{$officer->id}?token=valid-token-456", [], [], [], [
            'CONTENT_TYPE' => 'application/atom+xml',
        ], $atomXml)->assertNoContent();

        Queue::assertPushed(VerifyAndProcessLiveWebhookJob::class, function ($job) use ($officer) {
            return $job->officerId === $officer->id
                && $job->videoId === 'dQw4w9WgXcQ'
                && $job->title === 'Patroli LSPD 10-8 #imepolice';
        });

        // Deleted entry should not dispatch job
        Queue::fake();
        $deletedXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns:at="http://purl.org/atompub/tombstones/1.0" xmlns="http://www.w3.org/2005/Atom">
  <at:deleted-entry ref="yt:video:dQw4w9WgXcQ" when="2026-11-25T10:05:00+00:00" />
</feed>
XML;

        $this->call('POST', "/webhooks/youtube/websub/{$officer->id}?token=valid-token-456", [], [], [], [
            'CONTENT_TYPE' => 'application/atom+xml',
        ], $deletedXml)->assertNoContent();

        Queue::assertNothingPushed();
    }

    public function test_websub_post_enforces_hmac_signature_when_secret_configured(): void
    {
        Queue::fake();
        config(['services.youtube_websub.secret' => 'my-hub-secret']);

        $officer = $this->createOfficer();
        YouTubeWebSubSubscription::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'callback_token' => 'valid-token-789',
            'status' => 'active',
        ]);

        $atomXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns:yt="http://www.youtube.com/xml/schemas/2015" xmlns="http://www.w3.org/2005/Atom">
  <entry>
    <yt:videoId>AbCdEfGhIjK</yt:videoId>
    <yt:channelId>{$officer->channel_id}</yt:channelId>
    <title>Patroli #imepolice</title>
  </entry>
</feed>
XML;

        $this->call('POST', "/webhooks/youtube/websub/{$officer->id}?token=valid-token-789", [], [], [], [
            'CONTENT_TYPE' => 'application/atom+xml',
            'HTTP_X_HUB_SIGNATURE' => 'sha1=invalid',
        ], $atomXml)->assertStatus(403);

        $validSig = 'sha1=' . hash_hmac('sha1', $atomXml, 'my-hub-secret');
        $this->call('POST', "/webhooks/youtube/websub/{$officer->id}?token=valid-token-789", [], [], [], [
            'CONTENT_TYPE' => 'application/atom+xml',
            'HTTP_X_HUB_SIGNATURE' => $validSig,
        ], $atomXml)->assertNoContent();

        Queue::assertPushed(VerifyAndProcessLiveWebhookJob::class);
    }
}
