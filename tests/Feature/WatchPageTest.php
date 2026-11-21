<?php

namespace Tests\Feature;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\TacClip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WatchPageTest extends TestCase
{
    use RefreshDatabase;

    private function officer(string $name): Officer
    {
        return Officer::create([
            'officer_name' => $name,
            'streamer_name' => $name,
            'handle' => '@' . strtolower($name),
            'channel_id' => 'UC_' . strtoupper($name),
            'department' => 'LSPD',
            'rank' => 'Officer',
            'callsign' => '1-A-' . substr(md5($name), 0, 3),
            'is_active' => true,
        ]);
    }

    private function liveStream(Officer $officer, string $videoId, int $viewers = 10): ActiveStream
    {
        return ActiveStream::create([
            'officer_id' => $officer->id,
            'channel_id' => $officer->channel_id,
            'video_id' => $videoId,
            'title' => "Patroli {$officer->officer_name}",
            'status' => 'LIVE',
            'viewers_count' => $viewers,
        ]);
    }

    public function test_live_stream_renders_with_chat_and_other_live_streams(): void
    {
        $this->liveStream($this->officer('Alpha'), 'AAAAAAAAAAA', 50);
        $this->liveStream($this->officer('Bravo'), 'BBBBBBBBBBB', 20);

        $this->get('/watch/AAAAAAAAAAA')->assertInertia(fn (Assert $page) => $page
            ->component('Watch')
            ->where('stream.video_id', 'AAAAAAAAAAA')
            ->where('stream.is_live', true)
            ->where('stream.officer.officer_name', 'Alpha')
            ->where('stream.live_chat_url', fn ($url) => str_contains($url, 'live_chat?v=AAAAAAAAAAA'))
            ->has('liveStreams', 1)
            ->where('liveStreams.0.video_id', 'BBBBBBBBBBB'));
    }

    public function test_replay_from_cache_renders_without_chat(): void
    {
        Cache::put('cinema_hub_replays_cache', [[
            'video_id' => 'RRRRRRRRRRR',
            'title' => 'Arsip patroli',
            'thumbnail' => 'https://i.ytimg.com/vi/RRRRRRRRRRR/hqdefault.jpg',
            'streamed_at' => '2 jam yang lalu',
            'officer' => ['id' => 1, 'officer_name' => 'Charlie'],
        ]]);

        $this->get('/watch/RRRRRRRRRRR')->assertInertia(fn (Assert $page) => $page
            ->component('Watch')
            ->where('stream.is_live', false)
            ->where('stream.live_chat_url', null)
            ->where('stream.officer.officer_name', 'Charlie')
            ->has('replays', 0));
    }

    public function test_unknown_video_still_renders_player(): void
    {
        $this->get('/watch/ZZZZZZZZZZZ')->assertInertia(fn (Assert $page) => $page
            ->component('Watch')
            ->where('stream.video_id', 'ZZZZZZZZZZZ')
            ->where('stream.officer', null));
    }

    public function test_watch_page_lists_tacclips_and_reads_start_and_clip_params(): void
    {
        $alpha = $this->officer('Alpha');
        $this->liveStream($alpha, 'AAAAAAAAAAA');

        $make = fn (string $video, int $start, bool $approved = true) => TacClip::create([
            'officer_id' => $alpha->id,
            'video_id' => $video,
            'title' => "Momen {$video} {$start}",
            'start_seconds' => $start,
            'end_seconds' => $start + 30,
            'officer_name' => 'Alpha',
            'creator_name' => 'Tester',
            'is_approved' => $approved,
        ]);

        $later = $make('AAAAAAAAAAA', 300);
        $make('AAAAAAAAAAA', 60);
        $make('AAAAAAAAAAA', 90, false); // not approved: hidden
        $make('OTHERVIDEO1', 10);         // same officer, other video

        $this->get("/watch/AAAAAAAAAAA?t=295&clip={$later->id}")->assertInertia(fn (Assert $page) => $page
            ->component('Watch')
            ->has('clips', 2)
            ->where('clips.0.start_seconds', 60)
            ->where('clips.1.start_seconds', 300)
            ->has('officerClips', 1)
            ->where('officerClips.0.video_id', 'OTHERVIDEO1')
            ->where('startAt', 295)
            ->where('highlightClipId', $later->id));
    }

    public function test_invalid_video_id_returns_404(): void
    {
        $this->get('/watch/not-valid')->assertNotFound();
    }
}
