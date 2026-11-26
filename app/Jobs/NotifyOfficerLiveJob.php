<?php

namespace App\Jobs;

use App\Models\ActiveStream;
use App\Models\LiveNotificationLog;
use App\Models\Officer;
use App\Models\OfficerSubscription;
use App\Notifications\OfficerLiveNotification;
use App\Services\DiscordNotificationService;
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
        public readonly ?int $notificationLogId = null,
    ) {}

    public function handle(WebPushService $webPush, ?DiscordNotificationService $discord = null): void
    {
        $officer = Officer::find($this->officerId);
        $stream = ActiveStream::find($this->activeStreamId);

        if (! $officer || ! $officer->is_active || ! $stream || $stream->status !== 'LIVE') {
            return;
        }

        $discord ??= app(DiscordNotificationService::class);
        $pushEnabled = $webPush->isConfigured();
        $discordBotEnabled = $discord->isBotConfigured();

        // Mode 1: Broadcast 1x to configured Discord live channel
        if ($discord->isChannelConfigured()) {
            $discord->sendOfficerLiveToChannel($officer, $stream);
        }

        OfficerSubscription::where('officer_id', $officer->id)
            ->with(['user.pushSubscriptions'])
            ->chunkById(200, function ($subscriptions) use ($officer, $stream, $webPush, $pushEnabled, $discord, $discordBotEnabled): void {
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

                    // Mode 2: Personal Discord DM
                    if ($discordBotEnabled && $subscription->notify_discord_dm && $user->hasDiscordConnected()) {
                        $discord->sendOfficerLiveDm($user, $officer, $stream);
                    }
                }
            });

        $this->recordDeliveryTimestamp($stream);
    }

    private function recordDeliveryTimestamp(ActiveStream $stream): void
    {
        $log = $this->notificationLogId !== null
            ? LiveNotificationLog::find($this->notificationLogId)
            : LiveNotificationLog::where('video_id', $stream->video_id)->first();

        if (! $log) {
            return;
        }

        $notifiedAt = now();
        $deliveryDelay = $log->detected_at
            ? max(0, (int) $log->detected_at->diffInSeconds($notifiedAt, false))
            : null;
        $totalDelay = $log->youtube_started_at
            ? max(0, (int) $log->youtube_started_at->diffInSeconds($notifiedAt, false))
            : null;

        $log->update([
            'notified_at' => $notifiedAt,
            'delivery_delay_seconds' => $deliveryDelay,
            'total_delay_seconds' => $totalDelay,
        ]);
    }
}
