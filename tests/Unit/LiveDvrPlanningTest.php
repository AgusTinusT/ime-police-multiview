<?php

namespace Tests\Unit;

use App\Services\YouTubeClipperService;
use PHPUnit\Framework\TestCase;

/**
 * Live DVR cutting maths, checked against a real broadcast: YouTube numbers live
 * HLS segments from 0 at the start of the broadcast (lastSq * 2 s matched the
 * time since startTimestamp), and the embed's getCurrentTime() reports seconds
 * since that same start.
 */
class LiveDvrPlanningTest extends TestCase
{
    public function test_range_maps_seconds_since_start_to_segments(): void
    {
        // Moment at 1:30:00-1:30:30 of a stream with 2 s segments.
        $plan = YouTubeClipperService::planDvrRange(5400, 5430, 2.0);

        $this->assertSame(2700, $plan['startSq']);
        $this->assertSame(2715, $plan['endSq']);
        $this->assertSame(0.0, $plan['seekOffset']);
        $this->assertSame(30.0, $plan['duration']);
    }

    public function test_seek_offset_inside_the_first_segment(): void
    {
        $plan = YouTubeClipperService::planDvrRange(101.5, 120, 5.0);

        $this->assertSame(20, $plan['startSq']);
        $this->assertSame(24, $plan['endSq']);
        $this->assertEqualsWithDelta(1.5, $plan['seekOffset'], 0.0001);
    }

    public function test_invalid_ranges_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        YouTubeClipperService::planDvrRange(60, 30, 2.0);
    }

    public function test_live_edge_is_the_highest_segment_in_the_playlist(): void
    {
        $playlist = "#EXTM3U\n#EXT-X-MEDIA-SEQUENCE:6159\n#EXTINF:2.0,\nhttps://rr1.googlevideo.com/videoplayback/id/x/sq/6159/goap/a\n#EXTINF:2.0,\nhttps://rr1.googlevideo.com/videoplayback/id/x/sq/6160/goap/a\n";
        $this->assertSame(6160, YouTubeClipperService::lastSequenceNumber($playlist));

        $noSqUrls = "#EXTM3U\n#EXT-X-MEDIA-SEQUENCE:10\n#EXTINF:2.0,\nseg-a.ts\n#EXTINF:2.0,\nseg-b.ts\n#EXTINF:2.0,\nseg-c.ts\n";
        $this->assertSame(12, YouTubeClipperService::lastSequenceNumber($noSqUrls));

        $this->assertNull(YouTubeClipperService::lastSequenceNumber("#EXTM3U\n"));
    }

    public function test_permanent_video_errors(): void
    {
        $this->assertTrue(YouTubeClipperService::isPermanentVideoError('ERROR: [youtube] OmP29OQcXR0: Join this channel to get access to members-only content like this video'));
        $this->assertTrue(YouTubeClipperService::isPermanentVideoError('ERROR: [youtube] abc: Private video. Sign in if you\'ve been granted access'));
        $this->assertTrue(YouTubeClipperService::isPermanentVideoError('ERROR: [youtube] abc: Video unavailable. This video has been removed by the uploader'));
        $this->assertFalse(YouTubeClipperService::isPermanentVideoError('HTTP Error 503: Service Unavailable'));
        $this->assertFalse(YouTubeClipperService::isPermanentVideoError('Connection reset by peer'));
    }
}
