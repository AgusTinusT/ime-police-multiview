<?php

namespace Tests\Feature;

use App\Models\ActiveStream;
use App\Models\Officer;
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

    public function test_invalid_video_id_returns_404(): void
    {
        $this->get('/watch/not-valid')->assertNotFound();
    }
}
