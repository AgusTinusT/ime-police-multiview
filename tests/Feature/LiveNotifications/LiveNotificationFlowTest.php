<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\SyncOfficerStreamsJob;
use App\Jobs\VerifyAndProcessLiveWebhookJob;
use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Models\User;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LiveNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'channel_id' => 'UC1234567890123456789012',
            'handle' => '@patrolofficer',
            'streamer_name' => 'Patrol Streamer',
            'officer_name' => 'Capt. Price',
            'callsign' => '1-A-01',
            'department' => 'LSPD',
            'rank' => 'Captain',
            'is_active' => true,
            'is_listed' => true,
        ], $overrides));
    }

    public function test_webhook_job_verifies_live_patrol_sends_in_app_notification_and_deduplicates(): void
    {
        $officer = $this->createOfficer();
        $subscriber = User::factory()->create();
        $mutedInApp = User::factory()->create();
        $bannedUser = User::factory()->create(['status' => 'banned']);

        OfficerSubscription::create([
            'user_id' => $subscriber->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => true,
        ]);
        OfficerSubscription::create([
            'user_id' => $mutedInApp->id,
            'officer_id' => $officer->id,
            'notify_in_app' => false,
            'notify_web_push' => true,
        ]);
        OfficerSubscription::create([
            'user_id' => $bannedUser->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => true,
        ]);

        $scraper = Mockery::mock(YouTubeScraperService::class);
        $scraper->shouldReceive('getBatchStreamsTelemetry')
            ->with(['dQw4w9WgXcQ'])
            ->andReturn([
                'dQw4w9WgXcQ' => [
                    'video_id' => 'dQw4w9WgXcQ',
                    'status' => 'LIVE',
                    'viewers_count' => 145,
                    'title' => 'Patroli Malam 10-8 #imepolice',
                ],
            ]);
        $scraper->shouldReceive('scrapeVideoDetails')
            ->with('dQw4w9WgXcQ')
            ->andReturn([
                'title' => 'Patroli Malam 10-8 #imepolice',
                'description' => 'Live patroli LSPD #imepolice',
            ]);
        $scraper->shouldReceive('isPolicePatrolStream')
            ->andReturn(true);

        $job = new VerifyAndProcessLiveWebhookJob($officer->id, 'dQw4w9WgXcQ', 'Patroli Malam 10-8 #imepolice');
        $job->handle($scraper, app(LiveNotificationTriggerService::class));

        $stream = ActiveStream::where('officer_id', $officer->id)
            ->where('video_id', 'dQw4w9WgXcQ')
            ->first();

        $this->assertNotNull($stream);
        $this->assertSame('LIVE', $stream->status);
        $this->assertNotNull($stream->live_notified_at);
        $this->assertSame(1, $subscriber->notifications()->count());
        $this->assertSame(0, $mutedInApp->notifications()->count());
        $this->assertSame(0, $bannedUser->notifications()->count());

        // Running the webhook job again for the same video_id must NOT duplicate notifications
        $job->handle($scraper, app(LiveNotificationTriggerService::class));
        $this->assertSame(1, $subscriber->notifications()->count());

        // Running SyncOfficerStreamsJob afterwards for the same video_id must NOT duplicate notifications
        $syncScraper = Mockery::mock(YouTubeScraperService::class);
        $syncScraper->shouldReceive('syncAllActiveOfficers')->andReturn([
            $officer->id => [
                'video_id' => 'dQw4w9WgXcQ',
                'channel_id' => $officer->channel_id,
                'title' => 'Patroli Malam 10-8 #imepolice',
                'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
                'viewers_count' => 160,
                'description' => str_repeat('Live patroli LSPD #imepolice ', 10),
            ],
        ]);
        $syncScraper->shouldReceive('isPolicePatrolStream')->andReturn(true);
        $syncScraper->shouldReceive('fetchLatestOfficerVideos')->andReturn([]);

        (new SyncOfficerStreamsJob())->handle($syncScraper, app(LiveNotificationTriggerService::class));
        $this->assertSame(1, $subscriber->notifications()->count());
    }

    public function test_webhook_job_ignores_offline_or_non_patrol_videos(): void
    {
        $officer = $this->createOfficer();
        $subscriber = User::factory()->create();
        OfficerSubscription::create([
            'user_id' => $subscriber->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => true,
        ]);

        // Case 1: OFFLINE/upcoming video
        $offlineScraper = Mockery::mock(YouTubeScraperService::class);
        $offlineScraper->shouldReceive('getBatchStreamsTelemetry')
            ->with(['OffLineVid1'])
            ->andReturn(['OffLineVid1' => ['status' => 'OFFLINE']]);

        (new VerifyAndProcessLiveWebhookJob($officer->id, 'OffLineVid1', 'Upcoming Stream'))
            ->handle($offlineScraper, app(LiveNotificationTriggerService::class));

        $this->assertDatabaseCount('active_streams', 0);
        $this->assertSame(0, $subscriber->notifications()->count());

        // Case 2: LIVE but not police patrol
        $nonPatrolScraper = Mockery::mock(YouTubeScraperService::class);
        $nonPatrolScraper->shouldReceive('getBatchStreamsTelemetry')
            ->with(['BadSideVid1'])
            ->andReturn(['BadSideVid1' => ['status' => 'LIVE', 'viewers_count' => 50]]);
        $nonPatrolScraper->shouldReceive('scrapeVideoDetails')
            ->with('BadSideVid1')
            ->andReturn(['title' => 'Mabar Valorant', 'description' => 'Santai']);
        $nonPatrolScraper->shouldReceive('isPolicePatrolStream')->andReturn(false);

        (new VerifyAndProcessLiveWebhookJob($officer->id, 'BadSideVid1', 'Mabar Valorant'))
            ->handle($nonPatrolScraper, app(LiveNotificationTriggerService::class));

        $this->assertDatabaseCount('active_streams', 0);
        $this->assertSame(0, $subscriber->notifications()->count());
    }

    public function test_notification_center_api_lists_and_marks_read(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);

        $officer = $this->createOfficer();
        $stream = ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'dQw4w9WgXcQ',
            'title' => 'Patroli LSPD #imepolice',
            'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 88,
            'last_synced_at' => now(),
        ]);

        $user = User::factory()->create();
        OfficerSubscription::create([
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => true,
        ]);

        app(LiveNotificationTriggerService::class)->triggerIfNotNotified($officer, $stream);

        $list = $this->actingAs($user)->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJson(['unread_count' => 1])
            ->assertJsonCount(1, 'notifications');

        $notifId = $list->json('notifications.0.id');
        $this->assertSame('/watch/dQw4w9WgXcQ', $list->json('notifications.0.data.url'));

        $this->actingAs($user)
            ->postJson("/api/v1/notifications/{$notifId}/read")
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->actingAs($user)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJson(['unread_count' => 0]);
    }
}
