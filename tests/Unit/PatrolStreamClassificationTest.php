<?php

namespace Tests\Unit;

use App\Models\Officer;
use App\Services\YouTubeScraperService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PatrolStreamClassificationTest extends TestCase
{
    private function scraper(): YouTubeScraperService
    {
        return app(YouTubeScraperService::class);
    }

    public static function titles(): array
    {
        return [
            // #imepolice is the streamer's explicit opt-in and wins over badside words.
            'hashtag beats badside word' => ['Rampok tapi polisi2an dulu #imeroleplay #imepolice', true],
            'hashtag with heist' => ['Heist response LSPD #imepolice', true],
            'hashtag uppercase and spaced' => ['Patroli malam # IMEPOLICE', true],
            // Without the hashtag, badside words still exclude.
            'badside without hashtag' => ['Rampok bank bareng geng #imeroleplay', false],
            'badside even with polisi word' => ['Rampok tapi polisi2an dulu #imeroleplay', false],
            // Plain police signals still count.
            'police signal' => ['Patroli LSPD 10-8 #imeroleplay', true],
            'no signal at all' => ['Ngobrol santai #imeroleplay', false],
        ];
    }

    #[DataProvider('titles')]
    public function test_patrol_classification(string $title, bool $expected): void
    {
        $this->assertSame($expected, $this->scraper()->isPolicePatrolStream($title));
    }

    public function test_bypass_flag_still_accepts_everything(): void
    {
        $officer = new Officer(['bypass_hashtag_check' => true]);

        $this->assertTrue($this->scraper()->isPolicePatrolStream('Rampok bank', '', $officer));
    }

    public static function timeTexts(): array
    {
        return [
            'id finished' => ['Streaming 3 jam yang lalu', true],
            'en finished' => ['Streamed 2 days ago', true],
            'live now (empty)' => ['', false],
            'live viewers' => ['123 sedang menonton', false],
            'scheduled' => ['Dijadwalkan untuk 21/11/26, 20.00', false],
            'null' => [null, false],
        ];
    }

    #[DataProvider('timeTexts')]
    public function test_only_finished_streams_count_as_replays(?string $timeText, bool $expected): void
    {
        $this->assertSame($expected, YouTubeScraperService::isFinishedStreamTimeText($timeText));
    }
}
