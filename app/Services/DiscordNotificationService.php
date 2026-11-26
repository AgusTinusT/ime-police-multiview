<?php

namespace App\Services;

use App\Models\ActiveStream;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiscordNotificationService
{
    private const API_BASE = 'https://discord.com/api/v10';

    private const DEPT_COLORS = [
        'LSPD' => 0x2563EB,
        'BCSO' => 0xD97706,
        'SASP' => 0x38BDF8,
        'SAPR' => 0x16A34A,
    ];

    public function isBotConfigured(): bool
    {
        return trim((string) config('services.discord.bot_token', '')) !== '';
    }

    public function isOAuthConfigured(): bool
    {
        return trim((string) config('services.discord.client_id', '')) !== ''
            && trim((string) config('services.discord.client_secret', '')) !== '';
    }

    public function isChannelConfigured(): bool
    {
        $hasBotChannel = $this->isBotConfigured()
            && trim((string) config('services.discord.live_channel_id', '')) !== '';
        $hasWebhook = trim((string) config('services.discord.live_webhook_url', '')) !== '';

        return $hasBotChannel || $hasWebhook;
    }

    /**
     * Broadcast an officer LIVE alert once to the configured Discord channel.
     */
    public function sendOfficerLiveToChannel(Officer $officer, ActiveStream $stream): bool
    {
        if (! $this->isChannelConfigured()) {
            return false;
        }

        $pingRoleId = $this->resolvePingRoleId($officer);
        $content = $pingRoleId
            ? "<@&{$pingRoleId}> Perwira sedang memulai siaran langsung patroli!"
            : 'Perwira sedang memulai siaran langsung patroli!';

        $watchUrl = $this->absoluteUrl("/watch/{$stream->video_id}");
        $multiviewUrl = $this->absoluteUrl('/multiview');
        $embed = $this->buildOfficerLiveEmbed($officer, $stream, $watchUrl);

        $botToken = trim((string) config('services.discord.bot_token', ''));
        $channelId = trim((string) config('services.discord.live_channel_id', ''));

        if ($botToken !== '' && $channelId !== '') {
            $payload = [
                'content' => $content,
                'embeds' => [$embed],
                'components' => [
                    [
                        'type' => 1,
                        'components' => [
                            [
                                'type' => 2,
                                'style' => 5,
                                'label' => 'Tonton Live',
                                'url' => $watchUrl,
                            ],
                            [
                                'type' => 2,
                                'style' => 5,
                                'label' => 'Buka Multiview',
                                'url' => $multiviewUrl,
                            ],
                        ],
                    ],
                ],
                'allowed_mentions' => $pingRoleId
                    ? ['roles' => [$pingRoleId]]
                    : ['parse' => []],
            ];

            try {
                $response = Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bot {$botToken}"])
                    ->post(self::API_BASE . "/channels/{$channelId}/messages", $payload);

                if ($response->successful()) {
                    return true;
                }

                Log::warning('Discord live channel bot post failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'officer_id' => $officer->id,
                ]);
            } catch (Throwable $e) {
                Log::warning('Discord live channel bot exception: ' . $e->getMessage());
            }
        }

        $webhookUrl = trim((string) config('services.discord.live_webhook_url', ''));
        if ($webhookUrl !== '') {
            try {
                $response = Http::timeout(10)->post($webhookUrl, [
                    'username' => 'SASP Live Dispatch',
                    'content' => $content,
                    'embeds' => [$embed],
                    'allowed_mentions' => $pingRoleId
                        ? ['roles' => [$pingRoleId]]
                        : ['parse' => []],
                ]);

                if ($response->successful()) {
                    return true;
                }

                Log::warning('Discord live channel webhook failed', [
                    'status' => $response->status(),
                    'officer_id' => $officer->id,
                ]);
            } catch (Throwable $e) {
                Log::warning('Discord live channel webhook exception: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Send a personal Discord DM to a user when a followed officer goes LIVE.
     *
     * @return array{sent: bool, error_code: int|null, message: string|null}
     */
    public function sendOfficerLiveDm(User $user, Officer $officer, ActiveStream $stream): array
    {
        if (! $this->isBotConfigured() || ! $user->hasDiscordConnected()) {
            return ['sent' => false, 'error_code' => null, 'message' => 'Discord tidak terkonfigurasi.'];
        }

        $watchUrl = $this->absoluteUrl("/watch/{$stream->video_id}");
        $settingsUrl = $this->absoluteUrl('/settings?tab=notifications');

        $payload = [
            'content' => 'Perwira yang kamu ikuti baru saja mulai **LIVE Patroli**:',
            'embeds' => [$this->buildOfficerLiveEmbed($officer, $stream, $watchUrl, true)],
            'components' => [
                [
                    'type' => 1,
                    'components' => [
                        [
                            'type' => 2,
                            'style' => 5,
                            'label' => 'Tonton Sekarang',
                            'url' => $watchUrl,
                        ],
                        [
                            'type' => 2,
                            'style' => 5,
                            'label' => 'Atur Notifikasi',
                            'url' => $settingsUrl,
                        ],
                    ],
                ],
            ],
        ];

        return $this->sendDirectMessage($user, $payload);
    }

    /**
     * Send a test DM so the user can verify their Discord DM connection immediately.
     *
     * @return array{sent: bool, error_code: int|null, message: string|null}
     */
    public function sendTestDm(User $user): array
    {
        if (! $this->isBotConfigured() || ! $user->hasDiscordConnected()) {
            return ['sent' => false, 'error_code' => null, 'message' => 'Discord Bot belum dikonfigurasi atau akun belum terhubung.'];
        }

        $settingsUrl = $this->absoluteUrl('/settings?tab=notifications');

        return $this->sendDirectMessage($user, [
            'embeds' => [
                [
                    'title' => 'Notifikasi Discord DM Aktif (Beta)',
                    'description' => 'Koneksi berhasil! Kamu akan menerima DM pribadi di sini setiap kali perwira yang kamu ikuti mulai LIVE patroli.',
                    'color' => 0x22C55E,
                    'footer' => ['text' => 'IME RP · SASP Police Duty · Fitur Beta'],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
            'components' => [
                [
                    'type' => 1,
                    'components' => [
                        [
                            'type' => 2,
                            'style' => 5,
                            'label' => 'Kelola Notifikasi',
                            'url' => $settingsUrl,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{sent: bool, error_code: int|null, message: string|null}
     */
    protected function sendDirectMessage(User $user, array $payload): array
    {
        $botToken = trim((string) config('services.discord.bot_token', ''));
        $dmChannelId = $user->discord_dm_channel_id;

        try {
            if (empty($dmChannelId)) {
                $openResponse = Http::timeout(10)
                    ->withHeaders(['Authorization' => "Bot {$botToken}"])
                    ->post(self::API_BASE . '/users/@me/channels', [
                        'recipient_id' => (string) $user->discord_id,
                    ]);

                if (! $openResponse->successful()) {
                    return $this->handleDmFailure($user, $openResponse->json('code'), $openResponse->status());
                }

                $dmChannelId = (string) ($openResponse->json('id') ?? '');
                if ($dmChannelId === '') {
                    return ['sent' => false, 'error_code' => null, 'message' => 'Gagal membuka saluran DM Discord.'];
                }

                $user->forceFill(['discord_dm_channel_id' => $dmChannelId])->save();
            }

            $msgResponse = Http::timeout(10)
                ->withHeaders(['Authorization' => "Bot {$botToken}"])
                ->post(self::API_BASE . "/channels/{$dmChannelId}/messages", $payload);

            if ($msgResponse->successful()) {
                if (! empty($user->discord_dm_failed_reason)) {
                    $user->forceFill(['discord_dm_failed_reason' => null])->save();
                }

                return ['sent' => true, 'error_code' => null, 'message' => null];
            }

            return $this->handleDmFailure($user, $msgResponse->json('code'), $msgResponse->status());
        } catch (Throwable $e) {
            Log::warning('Discord DM exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return ['sent' => false, 'error_code' => null, 'message' => 'Gagal menghubungi server Discord.'];
        }
    }

    /**
     * @return array{sent: bool, error_code: int|null, message: string}
     */
    protected function handleDmFailure(User $user, mixed $discordCode, int $httpStatus): array
    {
        $code = is_numeric($discordCode) ? (int) $discordCode : null;

        if ($code === 50007) {
            $reason = 'Bot tidak dapat mengirim DM (Error 50007). Pastikan kamu sudah bergabung di server Discord komunitas dan mengaktifkan "Allow direct messages from server members".';
            $user->forceFill(['discord_dm_failed_reason' => $reason])->save();

            return ['sent' => false, 'error_code' => 50007, 'message' => $reason];
        }

        Log::warning('Discord DM delivery failed', [
            'user_id' => $user->id,
            'http_status' => $httpStatus,
            'discord_code' => $code,
        ]);

        return [
            'sent' => false,
            'error_code' => $code,
            'message' => 'Gagal mengirim DM Discord. Coba lagi beberapa saat.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOfficerLiveEmbed(Officer $officer, ActiveStream $stream, string $watchUrl, bool $isDm = false): array
    {
        $officerLabel = trim(($officer->callsign ? "[{$officer->callsign}] " : '') . $officer->officer_name);
        $dept = strtoupper((string) ($officer->department ?: 'SASP'));
        $color = $isDm ? 0xEF4444 : (self::DEPT_COLORS[$dept] ?? 0x3B82F6);

        $embed = [
            'title' => "{$officerLabel} sedang LIVE Patroli!",
            'url' => $watchUrl,
            'description' => $stream->title ?: 'Siaran langsung patroli kepolisian baru saja dimulai.',
            'color' => $color,
            'fields' => [
                [
                    'name' => 'Departemen',
                    'value' => $dept,
                    'inline' => true,
                ],
                [
                    'name' => 'Callsign',
                    'value' => $officer->callsign ?: '-',
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'IME RP · SASP Police Duty · Notifikasi Live (Beta)',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $avatarUrl = $officer->public_avatar_url;
        if (! empty($avatarUrl) && str_starts_with($avatarUrl, 'http')) {
            $embed['thumbnail'] = ['url' => $avatarUrl];
        }

        if (! empty($stream->thumbnail_url) && str_starts_with($stream->thumbnail_url, 'http')) {
            $embed['image'] = ['url' => $stream->thumbnail_url];
        }

        return $embed;
    }

    protected function resolvePingRoleId(Officer $officer): ?string
    {
        $dept = strtoupper((string) ($officer->department ?? ''));
        $deptRoles = (array) config('services.discord.dept_role_ids', []);
        $deptRole = trim((string) ($deptRoles[$dept] ?? ''));
        if ($deptRole !== '') {
            return $deptRole;
        }

        $globalRole = trim((string) config('services.discord.live_ping_role_id', ''));

        return $globalRole !== '' ? $globalRole : null;
    }

    protected function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url', 'https://sasp-motionliferp.com'), '/') . '/' . ltrim($path, '/');
    }
}
