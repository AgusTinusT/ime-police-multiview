<?php

namespace App\Services;

use App\Jobs\NotifyOfficerLiveJob;
use App\Models\ActiveStream;
use App\Models\Officer;
use Illuminate\Support\Facades\DB;

class LiveNotificationTriggerService
{
    /**
     * Atomically lock the ActiveStream row and dispatch NotifyOfficerLiveJob once per video_id.
     */
    public function triggerIfNotNotified(Officer $officer, ActiveStream $stream): bool
    {
        $shouldDispatch = DB::transaction(function () use ($stream): bool {
            $locked = ActiveStream::where('id', $stream->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'LIVE' || $locked->live_notified_at !== null) {
                return false;
            }

            $locked->forceFill(['live_notified_at' => now()])->save();

            return true;
        });

        if ($shouldDispatch) {
            NotifyOfficerLiveJob::dispatch($officer->id, $stream->id);
        }

        return $shouldDispatch;
    }
}
