<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BagibagiLeaderboardService
{
    private const STALE_TTL_SECONDS = 86400;
    private const BACKOFF_TTL_SECONDS = 60;

    /**
     * Ambil daftar top donatur dari endpoint partner BagiBagi dengan cache & fallback aman.
     *
     * @return array{
     *     enabled: bool,
     *     synced: bool,
     *     synced_date: ?string,
     *     donators: array<int, array{rank: int, name: string, amount: int, is_verified: bool, is_anonymous: bool}>,
     *     total_amount: int
     * }
     */
    public function getLeaderboard(): array
    {
        $url = $this->resolveEndpointUrl();

        if ($url === null) {
            return $this->emptyResult(enabled: false);
        }

        $hash = substr(sha1($url), 0, 16);
        $freshKey = "bagibagi:leaderboard:fresh:{$hash}";
        $staleKey = "bagibagi:leaderboard:stale:{$hash}";
        $backoffKey = "bagibagi:leaderboard:backoff:{$hash}";

        $ttl = max(0, (int) config('support.bagibagi.cache_ttl', 300));

        if ($ttl > 0) {
            $cached = Cache::get($freshKey);
            if (is_array($cached)) {
                return $cached;
            }

            if (Cache::has($backoffKey)) {
                $stale = Cache::get($staleKey);

                return is_array($stale) ? $stale : $this->emptyResult(enabled: true);
            }
        }

        $fetched = $this->fetchFromRemote($url);

        if ($fetched !== null) {
            if ($ttl > 0) {
                Cache::put($freshKey, $fetched, $ttl);
                Cache::put($staleKey, $fetched, self::STALE_TTL_SECONDS);
            }

            return $fetched;
        }

        if ($ttl > 0) {
            Cache::put($backoffKey, true, self::BACKOFF_TTL_SECONDS);
            $stale = Cache::get($staleKey);
            if (is_array($stale)) {
                return $stale;
            }
        }

        return $this->emptyResult(enabled: true);
    }

    /**
     * Menerima baik streamKey mentah maupun URL lengkap hasil salin dari dashboard BagiBagi.
     */
    public function resolveEndpointUrl(): ?string
    {
        $raw = trim((string) config('support.bagibagi.stream_key', ''));

        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw) === 1) {
            return $raw;
        }

        $endpoint = rtrim(
            (string) config(
                'support.bagibagi.endpoint',
                'https://bagibagi.co/api/partnerintegration/top-donator/streamkey'
            ),
            '?&'
        );

        $separator = str_contains($endpoint, '?') ? '&' : '?';

        return $endpoint . $separator . http_build_query(['streamkey' => $raw]);
    }

    private function fetchFromRemote(string $url): ?array
    {
        $timeout = max(1, (int) config('support.bagibagi.timeout', 5));

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('BagiBagi leaderboard HTTP error', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $payload = $response->json();

            if (! is_array($payload) || ($payload['success'] ?? false) !== true || ! is_array($payload['data'] ?? null)) {
                Log::warning('BagiBagi leaderboard payload invalid', [
                    'message' => is_array($payload) ? ($payload['message'] ?? null) : 'non-json',
                ]);

                return null;
            }

            $rows = collect($payload['data'])
                ->filter(fn ($item) => is_array($item) && (int) ($item['amount'] ?? 0) > 0)
                ->map(function (array $item) {
                    $name = trim((string) ($item['userName'] ?? ''));

                    return [
                        'name' => $name !== '' ? $name : 'Seseorang',
                        'amount' => (int) $item['amount'],
                        'is_verified' => (bool) ($item['isVerified'] ?? false),
                        'is_anonymous' => (bool) ($item['isAnonymous'] ?? false),
                    ];
                })
                ->sortByDesc('amount')
                ->values()
                ->map(fn (array $item, int $index) => array_merge(['rank' => $index + 1], $item))
                ->all();

            return [
                'enabled' => true,
                'synced' => true,
                'synced_date' => now()->toDateString(),
                'donators' => $rows,
                'total_amount' => array_sum(array_column($rows, 'amount')),
            ];
        } catch (\Throwable $e) {
            Log::warning('BagiBagi leaderboard request exception', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function emptyResult(bool $enabled): array
    {
        return [
            'enabled' => $enabled,
            'synced' => false,
            'synced_date' => null,
            'donators' => [],
            'total_amount' => 0,
        ];
    }
}
