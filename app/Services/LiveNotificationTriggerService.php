<?php

namespace App\Services;

use App\Jobs\NotifyOfficerLiveJob;
use App\Models\ActiveStream;
use App\Models\LiveNotificationLog;
use App\Models\Officer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LiveNotificationTriggerService
{
    /**
     * Atomically lock the ActiveStream row, record passive latency telemetry,
     * and dispatch NotifyOfficerLiveJob once per video_id.
     */
    public function triggerIfNotNotified(
        Officer $officer,
        ActiveStream $stream,
        string $detectionSource = 'sync',
        ?string $youtubeStartedAt = null,
    ): bool {
        $detectedAt = now();

        $shouldDispatch = DB::transaction(function () use ($stream, $detectedAt): bool {
            $locked = ActiveStream::where('id', $stream->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'LIVE' || $locked->live_notified_at !== null) {
                return false;
            }

            $locked->forceFill(['live_notified_at' => $detectedAt])->save();

            return true;
        });

        if (! $shouldDispatch) {
            return false;
        }

        $parsedStart = $this->parseTimestamp($youtubeStartedAt);
        $detectionDelay = $parsedStart !== null
            ? max(0, (int) $parsedStart->diffInSeconds($detectedAt, false))
            : null;

        $log = LiveNotificationLog::updateOrCreate(
            ['video_id' => $stream->video_id],
            [
                'officer_id' => $officer->id,
                'title' => $stream->title,
                'detection_source' => $detectionSource,
                'youtube_started_at' => $parsedStart,
                'detected_at' => $detectedAt,
                'detection_delay_seconds' => $detectionDelay,
            ],
        );

        NotifyOfficerLiveJob::dispatch($officer->id, $stream->id, $log->id);

        return true;
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
