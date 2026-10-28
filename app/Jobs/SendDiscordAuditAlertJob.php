<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDiscordAuditAlertJob implements ShouldQueue
{
    use Queueable;

    public string $action;
    public string $subjectType;
    public int $subjectId;
    public ?string $reason;
    public ?string $actorName;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $action,
        string $subjectType,
        int $subjectId,
        ?string $reason = null,
        ?string $actorName = null
    ) {
        $this->action = $action;
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;
        $this->reason = $reason;
        $this->actorName = $actorName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookUrl = config('biolinks.discord_webhook_url');
        if (empty($webhookUrl)) {
            return;
        }

        try {
            $payload = [
                'embeds' => [
                    [
                        'title' => "🛡️ Police Tactical Audit: {$this->action}",
                        'color' => 0x06b6d4, // Cyan
                        'fields' => [
                            ['name' => 'Actor', 'value' => $this->actorName ?: 'System / CLI', 'inline' => true],
                            ['name' => 'Subject', 'value' => "{$this->subjectType} #{$this->subjectId}", 'inline' => true],
                            ['name' => 'Reason', 'value' => $this->reason ?: 'N/A', 'inline' => false],
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
            ];

            Http::timeout(5)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::warning("Discord audit webhook alert job failed: " . $e->getMessage());
        }
    }
}
