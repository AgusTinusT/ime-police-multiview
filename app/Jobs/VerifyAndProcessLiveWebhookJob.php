<?php

namespace App\Jobs;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Services\LiveNotificationTriggerService;
use App\Services\YouTubeScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class VerifyAndProcessLiveWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly int $officerId,
        public readonly string $videoId,
        public readonly ?string $title = null,
    ) {}

    public function handle(YouTubeScraperService $scraper, LiveNotificationTriggerService $trigger): void
    {
        $officer = Officer::roster()->find($this->officerId);
        if (! $officer) {
            return;
        }

        $telemetryMap = $scraper->getBatchStreamsTelemetry([$this->videoId]);
        $telemetry = $telemetryMap[$this->videoId] ?? null;

        if (! $telemetry || ($telemetry['status'] ?? '') !== 'LIVE') {
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
