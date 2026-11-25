<?php

namespace App\Jobs;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Notifications\OfficerLiveNotification;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyOfficerLiveJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $officerId,
        public readonly int $activeStreamId,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $officer = Officer::find($this->officerId);
        $stream = ActiveStream::find($this->activeStreamId);

        if (! $officer || ! $officer->is_active || ! $stream || $stream->status !== 'LIVE') {
            return;
        }

        $pushEnabled = $webPush->isConfigured();

        OfficerSubscription::where('officer_id', $officer->id)
            ->with(['user.pushSubscriptions'])
            ->chunkById(200, function ($subscriptions) use ($officer, $stream, $webPush, $pushEnabled): void {
                foreach ($subscriptions as $subscription) {
                    $user = $subscription->user;
                    if (! $user || $user->isBanned()) {
                        continue;
                    }

                    if ($subscription->notify_in_app) {
                        $user->notify(new OfficerLiveNotification($officer, $stream));
                    }

                    if ($pushEnabled && $subscription->notify_web_push && $user->pushSubscriptions->isNotEmpty()) {
                        $webPush->sendOfficerLiveNotification($user, $officer, $stream);
                    }
                }
            });
    }
}
