<?php

namespace App\Jobs;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class VerifyAndProcessLiveWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * Short re-check delays (seconds) when YouTube WebSub fires while a broadcast is still UPCOMING.
     *
     * @var array<int, int>
     */
    public const RECHECK_DELAYS_SECONDS = [20, 45, 90];

    public function __construct(
        public readonly int $officerId,
        public readonly string $videoId,
        public readonly ?string $title = null,
        public readonly int $recheckAttempt = 0,
    ) {}

    public function handle(YouTubeScraperService $scraper, LiveNotificationTriggerService $trigger): void
    {
        $officer = Officer::roster()->find($this->officerId);
        if (! $officer) {
            return;
        }

        if ($this->recheckAttempt > 0) {
            Cache::forget("yt_telemetry_{$this->videoId}");
        }

        $telemetryMap = $scraper->getBatchStreamsTelemetry([$this->videoId]);
        $telemetry = $telemetryMap[$this->videoId] ?? null;
        $status = $telemetry['status'] ?? '';

        if ($status === 'UPCOMING') {
            $nextDelay = self::RECHECK_DELAYS_SECONDS[$this->recheckAttempt] ?? null;
            if ($nextDelay !== null && ! ActiveStream::where('video_id', $this->videoId)->where('status', 'LIVE')->exists()) {
                self::dispatch(
                    $this->officerId,
                    $this->videoId,
                    $this->title,
                    $this->recheckAttempt + 1,
                )->delay(now()->addSeconds($nextDelay));
            }

            return;
        }

        if ($status !== 'LIVE') {
            return;
        }

        $details = $scraper->scrapeVideoDetails($this->videoId);
        $title = $details['title']
            ?? ($telemetry['title'] ?? null)
            ?? $this->title
            ?? ($officer->officer_name . ' Live Patrol');
        $description = (string) ($details['description'] ?? '');

        if (! $scraper->isPolicePatrolStream($title, $description, $officer)) {
            return;
        }

        ActiveStream::where('video_id', $this->videoId)
            ->where('officer_id', '!=', $officer->id)
            ->delete();

        $activeStream = ActiveStream::updateOrCreate(
            [
                'officer_id' => $officer->id,
                'video_id' => $this->videoId,
            ],
            [
                'channel_id' => $officer->channel_id ?: ('ch-' . $officer->id),
                'title' => $title,
                'thumbnail_url' => "https://i.ytimg.com/vi/{$this->videoId}/hqdefault.jpg",
                'status' => 'LIVE',
                'viewers_count' => (int) ($telemetry['viewers_count'] ?? 0),
                'description' => $description !== '' ? $description : null,
                'last_synced_at' => now(),
            ],
        );

        ActiveStream::where('officer_id', $officer->id)
            ->where('video_id', '!=', $this->videoId)
            ->where('status', 'LIVE')
            ->update([
                'status' => 'ENDED',
                'last_synced_at' => now(),
            ]);

        $trigger->triggerIfNotNotified($officer, $activeStream);
    }
}
