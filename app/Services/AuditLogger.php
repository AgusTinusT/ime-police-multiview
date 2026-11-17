<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Log an action to the append-only audit_logs table.
     */
    public static function log(
        string $action,
        string $subjectType,
        int $subjectId,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $reason = null,
        ?int $actorId = null
    ): AuditLog {
        $actorId = $actorId ?? Auth::id();

        $log = AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'reason' => $reason,
        ]);

        // Send Discord Tactical Notification asynchronously via Queue Job if configured (B-10)
        self::sendDiscordAlert($action, $subjectType, $subjectId, $reason);

        return $log;
    }

    /**
     * Dispatch Discord Webhook notification job if webhook URL is configured (B-10).
     */
    protected static function sendDiscordAlert(string $action, string $subjectType, int $subjectId, ?string $reason): void
    {
        $webhookUrl = config('biolinks.discord_webhook_url');
        if (empty($webhookUrl)) {
            return;
        }

        try {
            $user = Auth::user();
            $actorName = $user ? "{$user->name} (#{$user->id})" : "System / CLI";

            \App\Jobs\SendDiscordAuditAlertJob::dispatch(
                $action,
                $subjectType,
                $subjectId,
                $reason,
                $actorName
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Dispatching Discord audit alert job failed: " . $e->getMessage());
        }
    }
}
