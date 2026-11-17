<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeChannelVerifier
{
    /**
     * Generate an 80-bit entropy verification token (Format: SASP-VERIFY-XXXX-XXXX-XXXX-XXXX).
     */
    public static function generateToken(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = strlen($alphabet);
        $blocks = [];
        for ($i = 0; $i < 4; $i++) {
            $block = '';
            for ($j = 0; $j < 4; $j++) {
                $block .= $alphabet[random_int(0, $len - 1)];
            }
            $blocks[] = $block;
        }

        return 'SASP-VERIFY-' . implode('-', $blocks);
    }
    /**
     * Scan YouTube channel (about & live pages or API) and return sha256 hashes of all SASP-VERIFY tokens found.
     *
     * @return array<string>
     */
    public function findTokenHashes(string $channelId): array
    {
        $hashes = [];
        $pattern = '/SASP-VERIFY(?:-[A-Z0-9]{4}){4}/i';

        // Approach 1: YouTube Data API v3 (if key available)
        $apiKey = config('services.youtube.api_key') ?: config('services.youtube.key') ?: env('YOUTUBE_API_KEY');
        if (!empty($apiKey)) {
            try {
                $response = Http::timeout(10)->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet',
                    'id' => $channelId,
                    'key' => $apiKey,
                ]);

                if ($response->successful()) {
                    $items = $response->json('items', []);
                    foreach ($items as $item) {
                        $desc = $item['snippet']['description'] ?? '';
                        if (preg_match_all($pattern, $desc, $matches)) {
                            foreach ($matches[0] as $token) {
                                $hashes[] = hash('sha256', strtoupper($token));
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("YouTube Data API fetch failed for {$channelId}: " . $e->getMessage());
            }
        }

        // Approach 2: Fetch channel pages (about and live)
        $urls = [
            "https://www.youtube.com/channel/{$channelId}/about",
            "https://www.youtube.com/channel/{$channelId}/live",
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Accept-Language' => 'id-ID,en;q=0.8',
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $body = $response->body();
                    if (preg_match_all($pattern, $body, $matches)) {
                        foreach ($matches[0] as $token) {
                            $hashes[] = hash('sha256', strtoupper($token));
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("YouTube channel scrape failed for {$url}: " . $e->getMessage());
            }
        }

        return array_values(array_unique($hashes));
    }

    /**
     * Backward-compatible verify method.
     *
     * @return array{verified: bool, description: ?string, error: ?string}
     */
    public function verify(string $channelId, string $tokenHash, ?string $rawToken = null): array
    {
        $hashes = $this->findTokenHashes($channelId);
        $verified = in_array(strtolower($tokenHash), array_map('strtolower', $hashes), true);

        return [
            'verified' => $verified,
            'description' => null,
            'error' => $verified ? null : 'Kode belum ditemukan di deskripsi channel. Pastikan sudah disimpan, lalu coba lagi dalam beberapa menit.',
        ];
    }
}
