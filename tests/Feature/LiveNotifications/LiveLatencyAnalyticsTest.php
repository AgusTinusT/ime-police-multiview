<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\VerifyAndProcessLiveWebhookJob;
use App\Models\LiveNotificationLog;
use App\Models\Officer;
use App\Models\User;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LiveLatencyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'channel_id' => 'UC1234567890123456789012',
            'handle' => '@latencyofficer',
            'streamer_name' => 'Latency Streamer',
            'officer_name' => 'Sgt. Foley',
            'callsign' => '3-C-01',
            'department' => 'LSPD',
            'rank' => 'Sergeant',
            'is_active' => true,
            'is_listed' => true,
        ], $overrides));
    }

    public function test_webhook_job_records_passive_latency_log_with_youtube_start_time(): void
    {
        $officer = $this->createOfficer();
        $startedAt = now()->subSeconds(35)->utc()->toIso8601String();

        $scraper = Mockery::mock(YouTubeScraperService::class);
        $scraper->shouldReceive('getBatchStreamsTelemetry')
            ->with(['LatVid12345'])
            ->andReturn([
                'LatVid12345' => [
                    'video_id' => 'LatVid12345',
                    'status' => 'LIVE',
                    'viewers_count' => 95,
                    'title' => 'Patroli LSPD #imepolice',
                    'started_at' => $startedAt,
                ],
            ]);
        $scraper->shouldReceive('scrapeVideoDetails')
            ->with('LatVid12345')
            ->andReturn([
                'title' => 'Patroli LSPD #imepolice',
                'description' => 'Live patroli #imepolice',
                'started_at' => $startedAt,
            ]);
        $scraper->shouldReceive('isPolicePatrolStream')->andReturn(true);

        (new VerifyAndProcessLiveWebhookJob($officer->id, 'LatVid12345', 'Patroli LSPD #imepolice'))
            ->handle($scraper, app(LiveNotificationTriggerService::class));

        $log = LiveNotificationLog::where('video_id', 'LatVid12345')->first();

        $this->assertNotNull($log);
        $this->assertSame($officer->id, $log->officer_id);
        $this->assertSame('websub', $log->detection_source);
        $this->assertNotNull($log->youtube_started_at);
        $this->assertNotNull($log->detected_at);
        $this->assertNotNull($log->notified_at);
        $this->assertGreaterThanOrEqual(30, $log->detection_delay_seconds);
        $this->assertLessThanOrEqual(45, $log->detection_delay_seconds);
        $this->assertNotNull($log->delivery_delay_seconds);
        $this->assertNotNull($log->total_delay_seconds);
    }

    public function test_admin_live_latency_api_returns_summary_and_enforces_admin_role(): void
    {
        $officer = $this->createOfficer();
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'user']);

        LiveNotificationLog::create([
            'officer_id' => $officer->id,
            'video_id' => 'WebSubVid01',
            'title' => 'Patroli WebSub',
            'detection_source' => 'websub',
            'youtube_started_at' => now()->subMinutes(10),
            'detected_at' => now()->subMinutes(10)->addSeconds(18),
            'notified_at' => now()->subMinutes(10)->addSeconds(20),
            'detection_delay_seconds' => 18,
            'delivery_delay_seconds' => 2,
            'total_delay_seconds' => 20,
        ]);

        LiveNotificationLog::create([
            'officer_id' => $officer->id,
            'video_id' => 'SyncVid0002',
            'title' => 'Patroli Sync',
            'detection_source' => 'sync_hashtag',
            'youtube_started_at' => now()->subMinutes(5),
            'detected_at' => now()->subMinutes(5)->addSeconds(62),
            'notified_at' => now()->subMinutes(5)->addSeconds(65),
            'detection_delay_seconds' => 62,
            'delivery_delay_seconds' => 3,
            'total_delay_seconds' => 65,
        ]);

        // Unauthenticated -> 401
        $this->getJson('/api/v1/admin/live-latency')->assertStatus(401);

        // Non-admin -> 403
        $this->actingAs($member)->getJson('/api/v1/admin/live-latency')->assertStatus(403);

        // Admin -> 200 with accurate summary & breakdown
        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/live-latency?days=7')
            ->assertOk()
            ->assertJsonPath('summary.total_notifications', 2)
            ->assertJsonPath('summary.measured_count', 2)
            ->assertJsonPath('summary.avg_detection_seconds', 40)
            ->assertJsonCount(2, 'by_source')
            ->assertJsonCount(2, 'logs');

        // Source filter works
        $this->actingAs($admin)
            ->getJson('/api/v1/admin/live-latency?days=7&source=websub')
            ->assertOk()
            ->assertJsonPath('summary.total_notifications', 1)
            ->assertJsonPath('logs.0.video_id', 'WebSubVid01');

        // CLI report executes cleanly
        $this->artisan('live:latency-report --days=7')->assertExitCode(0);
    }
}
