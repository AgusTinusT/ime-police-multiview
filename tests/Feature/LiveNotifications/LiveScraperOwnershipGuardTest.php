<?php

namespace Tests\Feature\LiveNotifications;

use App\Jobs\SyncOfficerStreamsJob;
use App\Models\ActiveStream;
use App\Models\LiveNotificationLog;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Models\User;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class LiveScraperOwnershipGuardTest extends TestCase
{
    use RefreshDatabase;

    private function createOfficer(array $overrides = []): Officer
    {
        return Officer::create(array_merge([
            'channel_id' => 'UC1111111111111111111111',
            'handle' => '@burhantampol',
            'streamer_name' => 'Burhan Stream',
            'officer_name' => 'Burhan Tampol Junior',
            'callsign' => '2-B-10',
            'department' => 'BCSO',
            'rank' => 'Deputy',
            'is_active' => true,
            'is_listed' => true,
            'bypass_hashtag_check' => true,
        ], $overrides));
    }

    public function test_check_live_status_rejects_channel_page_with_sidebar_live_video_id(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head><title> - YouTube</title></head>
<body>
<script>
var ytInitialData = {"contents":{"related":{"videoId":"oZqlVyNgSJo","isLive":true}}};
</script>
</body>
</html>
HTML;

        Http::fake([
            'https://www.youtube.com/@burhantampol/live' => Http::response($html, 200),
        ]);

        $scraper = app(YouTubeScraperService::class);
        $this->assertNull($scraper->checkLiveStatus('@burhantampol'));
        $this->assertSame([], $scraper->checkLiveStatusMany(['@burhantampol']));
    }

    public function test_is_html_currently_live_does_not_fall_through_when_player_response_is_offline(): void
    {
        $playerResponse = json_encode([
            'playabilityStatus' => ['status' => 'OK'],
            'videoDetails' => [
                'videoId' => 'RealVod1234',
                'channelId' => 'UC1111111111111111111111',
                'title' => 'Arsip Patroli Kemarin',
                'isLive' => false,
            ],
            'microformat' => [
                'playerMicroformatRenderer' => [
                    'liveBroadcastDetails' => [
                        'isLiveNow' => false,
                        'endTimestamp' => '2026-11-27T10:00:00+00:00',
                    ],
                ],
            ],
        ]);

        // Unrelated sidebar live video appears later in the HTML
        $html = "<script>var ytInitialPlayerResponse = {$playerResponse};</script>"
            . '<script>var ytInitialData = {"sidebar":{"videoId":"oZqlVyNgSJo","isLive":true}};</script>';

        $scraper = app(YouTubeScraperService::class);
        $this->assertFalse($scraper->isHtmlCurrentlyLive($html));
    }

    public function test_check_live_status_accepts_genuine_live_watch_page_and_extracts_metadata(): void
    {
        $playerResponse = json_encode([
            'playabilityStatus' => ['status' => 'OK'],
            'videoDetails' => [
                'videoId' => 'RealLive123',
                'channelId' => 'UC1111111111111111111111',
                'title' => 'Patroli BCSO #imepolice',
                'isLive' => true,
            ],
            'microformat' => [
                'playerMicroformatRenderer' => [
                    'liveBroadcastDetails' => [
                        'isLiveNow' => true,
                        'startTimestamp' => '2026-11-27T13:00:00+00:00',
                    ],
                ],
            ],
        ]);

        $html = "<html><head><title>Patroli BCSO #imepolice - YouTube</title></head>"
            . "<body><script>var ytInitialPlayerResponse = {$playerResponse};</script></body></html>";

        Http::fake([
            'https://www.youtube.com/@burhantampol/live' => Http::response($html, 200),
        ]);

        $scraper = app(YouTubeScraperService::class);
        $result = $scraper->checkLiveStatus('@burhantampol');

        $this->assertNotNull($result);
        $this->assertSame('RealLive123', $result['video_id']);
        $this->assertSame('UC1111111111111111111111', $result['channel_id']);
        $this->assertSame('Patroli BCSO #imepolice', $result['title']);
        $this->assertSame('2026-11-27T13:00:00+00:00', $result['started_at']);
    }

    public function test_tier3_rejects_live_stream_owned_by_different_channel_even_with_bypass_flag(): void
    {
        $officer = $this->createOfficer([
            'channel_id' => 'UC1111111111111111111111',
            'handle' => '@burhantampol',
            'bypass_hashtag_check' => true,
        ]);

        $foreignPlayerResponse = json_encode([
            'playabilityStatus' => ['status' => 'OK'],
            'videoDetails' => [
                'videoId' => 'oZqlVyNgSJo',
                'channelId' => 'UC9999999999999999999999',
                'title' => 'Random Other Streamer Live',
                'isLive' => true,
            ],
            'microformat' => [
                'playerMicroformatRenderer' => [
                    'liveBroadcastDetails' => [
                        'isLiveNow' => true,
                        'startTimestamp' => '2026-11-27T13:30:00+00:00',
                    ],
                ],
            ],
        ]);

        $foreignHtml = "<html><head><title>Random Other Streamer Live - YouTube</title></head>"
            . "<body><script>var ytInitialPlayerResponse = {$foreignPlayerResponse};</script></body></html>";

        Http::fake([
            'https://www.youtube.com/results*' => Http::response('<html></html>', 200),
            'https://www.youtube.com/feeds/videos.xml*' => Http::response('<feed></feed>', 200),
            'https://www.youtube.com/@burhantampol/live' => Http::response($foreignHtml, 200),
        ]);

        $scraper = app(YouTubeScraperService::class);
        $liveMap = $scraper->syncAllActiveOfficers(collect([$officer]));

        $this->assertNull($liveMap[$officer->id] ?? null);
    }

    public function test_sync_job_preserves_existing_live_stream_and_skips_notification_on_mismatched_channel_or_empty_title(): void
    {
        $officer = $this->createOfficer();
        $subscriber = User::factory()->create();
        OfficerSubscription::create([
            'user_id' => $subscriber->id,
            'officer_id' => $officer->id,
            'notify_in_app' => true,
            'notify_web_push' => false,
        ]);

        // Officer is already live on their real stream
        $existingStream = ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => 'RealLive123',
            'title' => '[[DAY 56]] - Patroli BCSO #imepolice',
            'thumbnail_url' => 'https://i.ytimg.com/vi/RealLive123/hqdefault.jpg',
            'status' => 'LIVE',
            'viewers_count' => 120,
            'live_notified_at' => now()->subMinutes(10),
            'last_synced_at' => now()->subMinute(),
        ]);

        Cache::put('cinema_hub_replays_cache', [['video_id' => 'cached12345']], 900);

        // Case 1: Mismatched channel_id from scraper -> must skip and keep RealLive123 LIVE
        $mismatchScraper = Mockery::mock(YouTubeScraperService::class);
        $mismatchScraper->shouldReceive('syncAllActiveOfficers')->andReturn([
            $officer->id => [
                'video_id' => 'oZqlVyNgSJo',
                'channel_id' => 'UC9999999999999999999999',
                'title' => 'Other Streamer Live',
                'thumbnail_url' => 'https://i.ytimg.com/vi/oZqlVyNgSJo/hqdefault.jpg',
                'viewers_count' => 50,
                'detection_method' => 'DIRECT_HANDLE',
            ],
        ]);

        (new SyncOfficerStreamsJob())->handle($mismatchScraper, app(LiveNotificationTriggerService::class));

        $this->assertSame('LIVE', $existingStream->fresh()->status);
        $this->assertDatabaseMissing('active_streams', ['video_id' => 'oZqlVyNgSJo']);
        $this->assertSame(0, $subscriber->notifications()->count());

        // Case 2: Empty title (or video_id-only title) even after scrapeVideoDetails -> must skip and keep RealLive123 LIVE
        $emptyTitleScraper = Mockery::mock(YouTubeScraperService::class);
        $emptyTitleScraper->shouldReceive('syncAllActiveOfficers')->andReturn([
            $officer->id => [
                'video_id' => 'WxFliJRKMZk',
                'channel_id' => $officer->channel_id,
                'title' => '',
                'thumbnail_url' => 'https://i.ytimg.com/vi/WxFliJRKMZk/hqdefault.jpg',
                'viewers_count' => 0,
                'detection_method' => 'DIRECT_HANDLE',
            ],
        ]);
        $emptyTitleScraper->shouldReceive('scrapeVideoDetails')
            ->with('WxFliJRKMZk')
            ->andReturn(['video_id' => 'WxFliJRKMZk', 'title' => null, 'description' => '', 'started_at' => null]);

        (new SyncOfficerStreamsJob())->handle($emptyTitleScraper, app(LiveNotificationTriggerService::class));

        $this->assertSame('LIVE', $existingStream->fresh()->status);
        $this->assertDatabaseMissing('active_streams', ['video_id' => 'WxFliJRKMZk']);
        $this->assertSame(0, $subscriber->notifications()->count());
    }

    public function test_sync_job_maps_hashtag_search_and_direct_handle_detection_sources(): void
    {
        $officerA = $this->createOfficer([
            'channel_id' => 'UC1111111111111111111111',
            'handle' => '@officera',
            'callsign' => '1-A-10',
        ]);
        $officerB = $this->createOfficer([
            'channel_id' => 'UC2222222222222222222222',
            'handle' => '@officerb',
            'callsign' => '1-A-11',
        ]);

        Cache::put('cinema_hub_replays_cache', [['video_id' => 'cached12345']], 900);

        $scraper = Mockery::mock(YouTubeScraperService::class);
        $scraper->shouldReceive('syncAllActiveOfficers')->andReturn([
            $officerA->id => [
                'video_id' => 'HashVid1234',
                'channel_id' => $officerA->channel_id,
                'title' => 'Patroli A #imepolice',
                'thumbnail_url' => 'https://i.ytimg.com/vi/HashVid1234/hqdefault.jpg',
                'viewers_count' => 80,
                'description' => str_repeat('Patroli A #imepolice ', 10),
                'started_at' => now()->subSeconds(15)->utc()->toIso8601String(),
                'detection_method' => 'HASHTAG_SEARCH',
            ],
            $officerB->id => [
                'video_id' => 'HandVid1234',
                'channel_id' => $officerB->channel_id,
                'title' => 'Patroli B #imepolice',
                'thumbnail_url' => 'https://i.ytimg.com/vi/HandVid1234/hqdefault.jpg',
                'viewers_count' => 60,
                'description' => str_repeat('Patroli B #imepolice ', 10),
                'started_at' => now()->subSeconds(20)->utc()->toIso8601String(),
                'detection_method' => 'DIRECT_HANDLE',
            ],
        ]);
        $scraper->shouldReceive('isPolicePatrolStream')->andReturn(true);

        (new SyncOfficerStreamsJob())->handle($scraper, app(LiveNotificationTriggerService::class));

        $this->assertSame('sync_hashtag', LiveNotificationLog::where('video_id', 'HashVid1234')->value('detection_source'));
        $this->assertSame('sync_handle', LiveNotificationLog::where('video_id', 'HandVid1234')->value('detection_source'));
    }
}
