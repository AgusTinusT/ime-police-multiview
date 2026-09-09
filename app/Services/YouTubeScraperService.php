<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeScraperService
{
    /**
     * Check if a YouTube channel is currently streaming live.
     *
     * @param string $handle The YouTube channel handle (e.g. '@ExampleChannel' or 'ExampleChannel')
     * @return array|null Live stream data or null if not live/failed
     */
    public function checkLiveStatus(string $handle): ?array
    {
        if (!str_starts_with($handle, '@')) {
            $handle = '@' . $handle;
        }

        try {
            $url = "https://www.youtube.com/{$handle}/live";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(8)->get($url);

            if (!$response->successful()) {
                Log::warning("YouTube scraper HTTP request failed for {$handle} with status: " . $response->status());
                return null;
            }

            $effectiveUrl = (string) $response->effectiveUri();
            $body = $response->body();

            // Extract video ID from URL redirect, canonical, or json videoId match
            $videoId = null;
            if (preg_match('/watch\?v=([A-Za-z0-9_-]{11})/', $effectiveUrl, $matches)) {
                $videoId = $matches[1];
            } elseif (preg_match('/<link rel="canonical" href="[^"]*watch\?v=([A-Za-z0-9_-]{11})">/', $body, $matches)) {
                $videoId = $matches[1];
            } elseif (preg_match('/"videoId":"([A-Za-z0-9_-]{11})"/ ', $body, $matches)) {
                $videoId = $matches[1];
            }

            if (!$videoId) {
                return null; // Not live
            }

            $isLive = str_contains($body, '"isLive":true') || str_contains($body, '"isLiveContent":true');
            $isPlayableNow = str_contains($body, '"playabilityStatus":{"status":"OK"');

            if (str_contains($body, '"status":"LIVE_STREAM_OFFLINE"')) {
                $isPlayableNow = false;
            }

            if ($isLive && $isPlayableNow) {
                $title = $this->extractTitle($body) ?? ($handle . " Police Patrol Live Feed");
                return [
                    'status' => 'LIVE',
                    'video_id' => $videoId,
                    'title' => $title,
                    'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                ];
            }
        } catch (\Exception $e) {
            Log::error("YouTube scraping failed for handle {$handle}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if multiple YouTube channels are streaming live in parallel.
     *
     * @param array $handles Array of YouTube channel handles
     * @return array Array of live stream data keyed by the original handle
     */
    public function checkLiveStatusMany(array $handles): array
    {
        if (empty($handles)) {
            return [];
        }

        try {
            // Initiate parallel HTTP requests using Laravel's Http::pool
            $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($handles) {
                foreach ($handles as $handle) {
                    $formattedHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
                    $url = "https://www.youtube.com/{$formattedHandle}/live";
                    
                    $pool->as($handle)->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept-Language' => 'en-US,en;q=0.9',
                    ])->timeout(8)->get($url);
                }
            });

            $results = [];

            foreach ($handles as $handle) {
                $response = $responses[$handle] ?? null;
                if (!$response || !$response->successful()) {
                    continue;
                }

                $effectiveUrl = (string) $response->effectiveUri();
                $body = $response->body();

                // Extract video ID
                $videoId = null;
                if (preg_match('/watch\?v=([A-Za-z0-9_-]{11})/', $effectiveUrl, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/<link rel="canonical" href="[^"]*watch\?v=([A-Za-z0-9_-]{11})">/', $body, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/"videoId":"([A-Za-z0-9_-]{11})"/ ', $body, $matches)) {
                    $videoId = $matches[1];
                }

                if (!$videoId) {
                    continue; // Not live
                }

                $isLive = str_contains($body, '"isLive":true') || str_contains($body, '"isLiveContent":true');
                $isPlayableNow = str_contains($body, '"playabilityStatus":{"status":"OK"');

                if (str_contains($body, '"status":"LIVE_STREAM_OFFLINE"')) {
                    $isPlayableNow = false;
                }

                if ($isLive && $isPlayableNow) {
                    $title = $this->extractTitle($body) ?? ($handle . " Police Patrol");
                    $results[$handle] = [
                        'status' => 'LIVE',
                        'video_id' => $videoId,
                        'title' => $title,
                        'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    ];
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error("Parallel YouTube scraping failed: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Search YouTube for live streams matching a keyword or hashtag.
     *
     * @param string $query Keyword or hashtag (e.g. '#imeroleplay burgenk')
     * @param int $limit Maximum number of live streams to return
     * @return array List of live stream records
     */
    public function searchLiveStreams(string $query, int $limit = 15): array
    {
        $cleanQuery = trim($query);
        if (empty($cleanQuery)) {
            return [];
        }

        try {
            $encodedQuery = urlencode($cleanQuery);
            // sp=EgJAAQ%3D%3D is the YouTube search filter specifically for "Live"
            $url = "https://www.youtube.com/results?search_query={$encodedQuery}&sp=EgJAAQ%3D%3D";

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->timeout(8)->get($url);

            if (!$response->successful()) {
                Log::warning("YouTube search request failed for query: {$cleanQuery} with status: " . $response->status());
                return [];
            }

            $html = $response->body();
            $data = null;

            if (preg_match('/var ytInitialData = ({.*?});<\/script>/s', $html, $matches) || preg_match('/ytInitialData\s*=\s*({.+?});/s', $html, $matches)) {
                $data = json_decode($matches[1], true);
            }

            if (!$data) {
                return [];
            }

            $contents = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
            $videos = [];

            foreach ($contents as $section) {
                $items = $section['itemSectionRenderer']['contents'] ?? [];
                foreach ($items as $item) {
                    if (isset($item['videoRenderer'])) {
                        $v = $item['videoRenderer'];
                        $videoId = $v['videoId'] ?? null;
                        if (!$videoId) {
                            continue;
                        }

                        $badges = $v['badges'] ?? [];
                        $overlays = $v['thumbnailOverlays'] ?? [];
                        
                        $isLive = false;
                        foreach ($overlays as $overlay) {
                            if (isset($overlay['thumbnailOverlayTimeStatusRenderer'])) {
                                $style = $overlay['thumbnailOverlayTimeStatusRenderer']['style'] ?? '';
                                if ($style === 'LIVE' || $style === 'LIVE_NOW') {
                                    $isLive = true;
                                }
                            }
                        }
                        foreach ($badges as $b) {
                            $label = $b['metadataBadgeRenderer']['label'] ?? '';
                            $style = $b['metadataBadgeRenderer']['style'] ?? '';
                            if (stripos($label, 'LIVE') !== false || stripos($style, 'LIVE') !== false) {
                                $isLive = true;
                            }
                        }

                        if ($isLive) {
                            $title = $v['title']['runs'][0]['text'] ?? ($v['headline']['simpleText'] ?? 'Live Stream');
                            $channelName = $v['ownerText']['runs'][0]['text'] ?? ($v['shortBylineText']['runs'][0]['text'] ?? 'Streamer');
                            $viewers = $v['viewCountText']['runs'][0]['text'] ?? ($v['viewCountText']['simpleText'] ?? 'Live');

                            $videos[] = [
                                'video_id' => $videoId,
                                'title' => $title,
                                'channel_name' => $channelName,
                                'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                                'viewers' => $viewers,
                                'status' => 'LIVE',
                            ];

                            if (count($videos) >= $limit) {
                                break 2;
                            }
                        }
                    }
                }
            }

            return $videos;
        } catch (\Exception $e) {
            Log::error("YouTube live search failed for query {$cleanQuery}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract title from YouTube HTML.
     */
    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title>(.*?)<\/title>/', $html, $matches)) {
            $title = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
            return trim(str_replace(' - YouTube', '', $title));
        }
        return null;
    }
}
