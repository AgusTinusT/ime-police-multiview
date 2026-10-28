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

        // Send Discord Tactical Notification if configured (especially for sensitive events)
        self::sendDiscordAlert($action, $subjectType, $subjectId, $reason);

        return $log;
    }

    /**
     * Send Discord Webhook notification if webhook URL is configured.
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

            $payload = [
                'embeds' => [
                    [
                        'title' => "🛡️ Police Tactical Audit: {$action}",
                        'color' => 0x06b6d4, // Cyan
                        'fields' => [
                            ['name' => 'Actor', 'value' => $actorName, 'inline' => true],
                            ['name' => 'Subject', 'value' => "{$subjectType} #{$subjectId}", 'inline' => true],
                            ['name' => 'Reason', 'value' => $reason ?: 'N/A', 'inline' => false],
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ]
                ]
            ];

            \Illuminate\Support\Facades\Http::timeout(3)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Discord audit webhook alert failed: " . $e->getMessage());
        }
    }
}
