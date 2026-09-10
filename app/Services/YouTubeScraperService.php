<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class YouTubeScraperService
{
    /**
     * Standard realistic browser headers with consent cookies to bypass VPS/Datacenter IP blocks.
     */
    public static function getBrowserHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cookie' => 'SOCS=CAISNQgDEitib3FfaWRlbnRpdHlmcm9udGVuZHVpc2VydmVyXzIwMjMwODI5LjA3X3AwGgJpZCADGgYIgJ_PpwY; CONSENT=YES+cb.20230531-04-p0.id+FX+119; PREF=tz=Asia.Jakarta;',
            'sec-ch-ua' => '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
        ];
    }

    /**
     * Hybrid Multi-Tier Fast Sync for all registered officers.
     * Tier 1: Hashtag live stream scan (#imepolice, #imeroleplay)
     * Tier 2: YouTube RSS XML feeds for officers with channel_id (bypasses VPS datacenter IP blocks)
     * Tier 3: Direct handle live endpoint check (@handle/live) for remaining unmatched officers
     *
     * @param iterable $officers Collection or array of Officer models
     * @return array Map of [officer_id => live_stream_data_or_null]
     */
    public function syncAllActiveOfficers(iterable $officers): array
    {
        $officerList = collect($officers);
        if ($officerList->isEmpty()) {
            return [];
        }

        $results = [];
        $unmatchedOfficers = collect();

        // --- TIER 1: Fast Hashtag Live Scan (#imepolice & #imeroleplay) ---
        $hashtagLiveStreams = [];
        foreach (['#imepolice', '#imeroleplay'] as $tag) {
            $streams = $this->searchLiveStreams($tag, 35);
            foreach ($streams as $s) {
                if (!empty($s['video_id'])) {
                    $hashtagLiveStreams[$s['video_id']] = $s;
                }
            }
        }

        // Match hashtag streams against officers
        foreach ($officerList as $officer) {
            $matchedStream = null;
            $cleanHandle = strtolower(ltrim($officer->handle, '@'));
            $streamerName = strtolower(trim($officer->streamer_name ?? ''));
            $officerName = strtolower(trim($officer->officer_name ?? ''));
            $callsign = strtolower(trim($officer->callsign ?? ''));
            $channelId = trim($officer->channel_id ?? '');

            foreach ($hashtagLiveStreams as $videoId => $stream) {
                $streamHandle = strtolower(ltrim($stream['handle'] ?? '', '@'));
                $streamBrowseId = trim($stream['channel_id'] ?? '');
                $chName = strtolower(trim($stream['channel_name'] ?? ''));
                $title = strtolower(trim($stream['title'] ?? ''));

                // Match condition 1: Exact handle match
                if (!empty($cleanHandle) && !empty($streamHandle) && $cleanHandle === $streamHandle) {
                    $matchedStream = $stream;
                    break;
                }

                // Match condition 2: Exact YouTube Channel ID (browseId)
                if (!empty($channelId) && !empty($streamBrowseId) && $channelId === $streamBrowseId) {
                    $matchedStream = $stream;
                    break;
                }

                // Match condition 3: Channel Name match
                if (!empty($chName) && (
                    (!empty($cleanHandle) && ($chName === $cleanHandle || str_contains($chName, $cleanHandle) || str_contains($cleanHandle, $chName))) ||
                    (!empty($streamerName) && ($chName === $streamerName || str_contains($chName, $streamerName) || str_contains($streamerName, $chName)))
                )) {
                    $matchedStream = $stream;
                    break;
                }

                // Match condition 4: Callsign in title
                if (!empty($callsign) && strlen($callsign) >= 4 && str_contains($title, $callsign)) {
                    $matchedStream = $stream;
                    break;
                }
            }

            if ($matchedStream) {
                $results[$officer->id] = [
                    'status' => 'LIVE',
                    'video_id' => $matchedStream['video_id'],
                    'title' => $matchedStream['title'],
                    'thumbnail_url' => $matchedStream['thumbnail_url'] ?? "https://i.ytimg.com/vi/{$matchedStream['video_id']}/hqdefault.jpg",
                    'viewers_count' => $matchedStream['viewers_count'] ?? 0,
                    'description' => $matchedStream['description'] ?? '',
                    'channel_id' => $matchedStream['channel_id'] ?? $officer->channel_id,
                    'detection_method' => 'HASHTAG_SEARCH',
                ];
            } else {
                $unmatchedOfficers->push($officer);
            }
        }

        // --- TIER 2: Zero-Quota RSS XML Feeds (Bulletproof on VPS Datacenter IPs) ---
        $rssOfficers = $unmatchedOfficers->filter(fn($o) => !empty($o->channel_id) && str_starts_with($o->channel_id, 'UC'));
        if ($rssOfficers->isNotEmpty()) {
            $rssCandidates = [];
            $rssResponses = Http::pool(function ($pool) use ($rssOfficers) {
                foreach ($rssOfficers as $officer) {
                    $pool->as($officer->id)->withHeaders(self::getBrowserHeaders())->timeout(5)->get("https://www.youtube.com/feeds/videos.xml?channel_id={$officer->channel_id}");
                }
            });

            foreach ($rssOfficers as $officer) {
                $res = $rssResponses[$officer->id] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) {
                    $xml = @simplexml_load_string($res->body());
                    if ($xml && isset($xml->entry[0])) {
                        $latest = $xml->entry[0];
                        $videoId = (string) $latest->children('yt', true)->videoId;
                        $title = (string) $latest->title;
                        $published = (string) $latest->published;
                        $pubTime = strtotime($published);

                        // If published in last 72 hours, check if it's currently live
                        if (!empty($videoId) && (time() - $pubTime) < 259200) {
                            $rssCandidates[$officer->id] = [
                                'officer' => $officer,
                                'video_id' => $videoId,
                                'title' => $title,
                                'published' => $published,
                            ];
                        }
                    }
                }
            }

            if (!empty($rssCandidates)) {
                $candidateIds = array_unique(array_column($rssCandidates, 'video_id'));
                $telemetry = $this->getBatchStreamsTelemetry($candidateIds);

                foreach ($rssCandidates as $officerId => $cand) {
                    $vId = $cand['video_id'];
                    $t = $telemetry[$vId] ?? null;
                    if ($t && ($t['status'] ?? '') === 'LIVE') {
                        $results[$officerId] = [
                            'status' => 'LIVE',
                            'video_id' => $vId,
                            'title' => $cand['title'],
                            'thumbnail_url' => "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg",
                            'viewers_count' => $t['viewers_count'] ?? 0,
                            'description' => '',
                            'channel_id' => $cand['officer']->channel_id,
                            'detection_method' => 'RSS_FEED',
                        ];
                        // Remove from unmatched
                        $unmatchedOfficers = $unmatchedOfficers->reject(fn($o) => $o->id == $officerId);
                    }
                }
            }
        }

        // --- TIER 3: Targeted Direct Handle Check for Remaining Unmatched Officers ---
        if ($unmatchedOfficers->isNotEmpty()) {
            $handlesToCheck = $unmatchedOfficers->pluck('handle')->toArray();
            $directLiveResults = $this->checkLiveStatusMany($handlesToCheck);

            foreach ($unmatchedOfficers as $officer) {
                $formattedHandle = str_starts_with($officer->handle, '@') ? $officer->handle : '@' . $officer->handle;
                $directData = $directLiveResults[$officer->handle] ?? ($directLiveResults[$formattedHandle] ?? null);

                if ($directData) {
                    $results[$officer->id] = [
                        'status' => 'LIVE',
                        'video_id' => $directData['video_id'],
                        'title' => $directData['title'],
                        'thumbnail_url' => $directData['thumbnail_url'],
                        'viewers_count' => $directData['viewers_count'] ?? 0,
                        'description' => $directData['description'] ?? '',
                        'channel_id' => $directData['channel_id'] ?? $officer->channel_id,
                        'detection_method' => 'DIRECT_HANDLE',
                    ];
                } else {
                    $results[$officer->id] = null;
                }
            }
        }

        return $results;
    }

    /**
     * Get live telemetry (viewers count & status) for a batch of video IDs with 30s cache.
     * Supports both optional YouTube Data API v3 and direct HTML scraper.
     *
     * @param array $videoIds Array of YouTube video IDs
     * @return array Map of videoId => telemetry data
     */
    public function getBatchStreamsTelemetry(array $videoIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('trim', $videoIds), function ($id) {
            return strlen($id) === 11 && preg_match('/^[A-Za-z0-9_-]{11}$/', $id);
        })));
        $cleanIds = array_slice($cleanIds, 0, 40);

        if (empty($cleanIds)) {
            return [];
        }

        $results = [];
        $uncachedIds = [];

        foreach ($cleanIds as $id) {
            $cached = Cache::get("yt_telemetry_{$id}");
            if ($cached !== null && is_array($cached)) {
                $results[$id] = $cached;
            } else {
                $uncachedIds[] = $id;
            }
        }

        if (empty($uncachedIds)) {
            return $results;
        }

        $apiKey = config('services.youtube.api_key', env('YOUTUBE_API_KEY'));

        // Strategy A: Official YouTube Data API v3 if API key is provided
        if (!empty($apiKey)) {
            try {
                $response = Http::timeout(6)->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,liveStreamingDetails',
                    'id' => implode(',', $uncachedIds),
                    'key' => $apiKey,
                ]);

                if ($response->successful()) {
                    $items = $response->json('items') ?? [];
                    $apiFoundIds = [];

                    foreach ($items as $item) {
                        $vId = $item['id'] ?? null;
                        if (!$vId) continue;
                        $apiFoundIds[] = $vId;

                        $liveBroadcastContent = $item['snippet']['liveBroadcastContent'] ?? '';
                        $liveDetails = $item['liveStreamingDetails'] ?? null;
                        $concurrentViewers = (int) ($liveDetails['concurrentViewers'] ?? 0);

                        $isLive = ($liveBroadcastContent === 'live');
                        $telemetry = [
                            'video_id' => $vId,
                            'viewers_count' => $concurrentViewers,
                            'status' => $isLive ? 'LIVE' : 'OFFLINE',
                            'title' => $item['snippet']['title'] ?? '',
                            'updated_at' => now()->toIso8601String(),
                        ];

                        Cache::put("yt_telemetry_{$vId}", $telemetry, 30);
                        $results[$vId] = $telemetry;
                    }

                    // Remaining IDs that were not returned by API
                    $uncachedIds = array_diff($uncachedIds, $apiFoundIds);
                    if (empty($uncachedIds)) {
                        return $results;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("YouTube Data API v3 telemetry fetch failed, falling back to scraper: " . $e->getMessage());
            }
        }

        // Strategy B: Parallel HTML Scraper (Zero API Quota, Datacenter Header Bypass)
        try {
            $responses = Http::pool(function ($pool) use ($uncachedIds) {
                foreach ($uncachedIds as $id) {
                    $pool->as($id)->withHeaders(self::getBrowserHeaders())->timeout(6)->get("https://www.youtube.com/watch?v={$id}");
                }
            });

            foreach ($uncachedIds as $id) {
                $res = $responses[$id] ?? null;
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) {
                    $body = $res->body();
                    $isLive = str_contains($body, '"isLive":true') || str_contains($body, '"isLiveContent":true') || str_contains($body, '"isLiveDvrEnabled":true');
                    $isOffline = str_contains($body, '"status":"LIVE_STREAM_OFFLINE"') || str_contains($body, 'STREAM_OFFLINE');
                    $isPlayable = str_contains($body, '"playabilityStatus":{"status":"OK"');
                    $viewersCount = $this->extractViewersCount($body);

                    $telemetry = [
                        'video_id' => $id,
                        'viewers_count' => $viewersCount,
                        'status' => ($isLive && $isPlayable && !$isOffline) ? 'LIVE' : 'OFFLINE',
                        'updated_at' => now()->toIso8601String(),
                    ];

                    Cache::put("yt_telemetry_{$id}", $telemetry, 30);
                    $results[$id] = $telemetry;
                } else {
                    $fallback = [
                        'video_id' => $id,
                        'viewers_count' => 0,
                        'status' => 'OFFLINE',
                        'updated_at' => now()->toIso8601String(),
                    ];
                    Cache::put("yt_telemetry_{$id}", $fallback, 15);
                    $results[$id] = $fallback;
                }
            }
        } catch (\Throwable $e) {
            Log::error("Batch stream telemetry fetch failed: " . $e->getMessage());
        }

        return $results;
    }

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
            
            $response = Http::withHeaders(self::getBrowserHeaders())->timeout(7)->get($url);

            if (!$response->successful()) {
                Log::warning("YouTube scraper HTTP request failed for {$handle} with status: " . $response->status());
                return null;
            }

            $effectiveUrl = (string) $response->effectiveUri();
            $body = $response->body();

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
                $description = $this->extractDescription($body);
                $viewersCount = $this->extractViewersCount($body);
                $channelId = $this->extractChannelId($body);

                return [
                    'status' => 'LIVE',
                    'video_id' => $videoId,
                    'title' => $title,
                    'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                    'viewers_count' => $viewersCount,
                    'description' => $description,
                    'channel_id' => $channelId,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("YouTube scraping failed for handle {$handle}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Check multiple YouTube channels for live status in small parallel chunks.
     *
     * @param array $handles Array of YouTube channel handles
     * @return array Array of live stream data keyed by handle
     */
    public function checkLiveStatusMany(array $handles): array
    {
        if (empty($handles)) {
            return [];
        }

        $results = [];
        $chunks = array_chunk($handles, 8); // Chunks of 8 to prevent VPS datacenter IP rate limits

        foreach ($chunks as $chunk) {
            try {
                $responses = Http::pool(function (\Illuminate\Http\Client\Pool $pool) use ($chunk) {
                    foreach ($chunk as $handle) {
                        $formattedHandle = str_starts_with($handle, '@') ? $handle : '@' . $handle;
                        $url = "https://www.youtube.com/{$formattedHandle}/live";
                        
                        $pool->as($handle)->withHeaders(self::getBrowserHeaders())->timeout(7)->get($url);
                    }
                });

                foreach ($chunk as $handle) {
                    $response = $responses[$handle] ?? null;
                    if (!$response || !($response instanceof \Illuminate\Http\Client\Response) || !$response->successful()) {
                        continue;
                    }

                    $effectiveUrl = (string) $response->effectiveUri();
                    $body = $response->body();

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
                        $description = $this->extractDescription($body);
                        $viewersCount = $this->extractViewersCount($body);
                        $channelId = $this->extractChannelId($body);

                        $results[$handle] = [
                            'status' => 'LIVE',
                            'video_id' => $videoId,
                            'title' => $title,
                            'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                            'viewers_count' => $viewersCount,
                            'description' => $description,
                            'channel_id' => $channelId,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::error("Parallel YouTube scraping chunk failed: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Search YouTube for live streams matching a keyword or hashtag.
     *
     * @param string $query Keyword or hashtag (e.g. '#imeroleplay' or '#imepolice')
     * @param int $limit Maximum number of live streams to return
     * @return array List of live stream records
     */
    public function searchLiveStreams(string $query, int $limit = 35): array
    {
        $cleanQuery = trim($query);
        if (empty($cleanQuery)) {
            return [];
        }

        try {
            $encodedQuery = urlencode($cleanQuery);
            // sp=EgJAAQ%3D%3D is the YouTube search filter for "Live"
            $url = "https://www.youtube.com/results?search_query={$encodedQuery}&sp=EgJAAQ%3D%3D";

            $response = Http::withHeaders(self::getBrowserHeaders())->timeout(7)->get($url);

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
                            
                            $ownerText = $v['ownerText'] ?? null;
                            $channelName = $ownerText['runs'][0]['text'] ?? ($v['shortBylineText']['runs'][0]['text'] ?? 'Streamer');
                            
                            // Extract handle & channelId if available in navigationEndpoint
                            $nav = $ownerText['runs'][0]['navigationEndpoint']['browseEndpoint'] ?? ($v['shortBylineText']['runs'][0]['navigationEndpoint']['browseEndpoint'] ?? null);
                            $canonical = $nav['canonicalBaseUrl'] ?? null;
                            $browseId = $nav['browseId'] ?? null;
                            $handle = null;
                            if ($canonical && str_starts_with($canonical, '/@')) {
                                $handle = ltrim($canonical, '/');
                            }

                            // Extract viewers count
                            $viewers = 'Live';
                            if (isset($v['viewCountText']['runs']) && is_array($v['viewCountText']['runs'])) {
                                $viewers = trim(implode('', array_column($v['viewCountText']['runs'], 'text')));
                            } elseif (isset($v['viewCountText']['simpleText'])) {
                                $viewers = trim($v['viewCountText']['simpleText']);
                            } elseif (isset($v['shortViewCountText']['simpleText'])) {
                                $viewers = trim($v['shortViewCountText']['simpleText']);
                            }

                            $viewersCount = (int) preg_replace('/[^\d]/', '', $viewers);

                            // Extract description snippet
                            $description = '';
                            if (isset($v['detailedMetadataSnippets'][0]['snippetText']['runs']) && is_array($v['detailedMetadataSnippets'][0]['snippetText']['runs'])) {
                                $description = trim(implode('', array_column($v['detailedMetadataSnippets'][0]['snippetText']['runs'], 'text')));
                            } elseif (isset($v['descriptionSnippet']['runs']) && is_array($v['descriptionSnippet']['runs'])) {
                                $description = trim(implode('', array_column($v['descriptionSnippet']['runs'], 'text')));
                            } elseif (isset($v['descriptionSnippet']['simpleText'])) {
                                $description = trim($v['descriptionSnippet']['simpleText']);
                            }

                            $videos[] = [
                                'video_id' => $videoId,
                                'title' => $title,
                                'channel_name' => $channelName,
                                'handle' => $handle,
                                'channel_id' => $browseId,
                                'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
                                'viewers' => $viewers,
                                'viewers_count' => $viewersCount,
                                'description' => $description,
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
        } catch (\Throwable $e) {
            Log::error("YouTube live search failed for query {$cleanQuery}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch complete details (full description, title, viewers) for a specific video ID.
     */
    public function scrapeVideoDetails(string $videoId): ?array
    {
        $cleanId = trim($videoId);
        if (strlen($cleanId) !== 11) {
            return null;
        }

        try {
            $response = Http::withHeaders(self::getBrowserHeaders())->timeout(7)->get("https://www.youtube.com/watch?v={$cleanId}");

            if (!$response->successful()) {
                return null;
            }

            $body = $response->body();
            $title = $this->extractTitle($body);
            $description = $this->extractDescription($body);
            $viewersCount = $this->extractViewersCount($body);

            return [
                'video_id' => $cleanId,
                'title' => $title,
                'description' => $description,
                'viewers_count' => $viewersCount,
                'thumbnail_url' => "https://i.ytimg.com/vi/{$cleanId}/hqdefault.jpg",
            ];
        } catch (\Throwable $e) {
            Log::error("Failed to scrape video details for {$cleanId}: " . $e->getMessage());
            return null;
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

    /**
     * Extract channel ID (browseId UC...) from YouTube HTML.
     */
    private function extractChannelId(string $html): ?string
    {
        if (preg_match('/"channelId":"(UC[A-Za-z0-9_-]{22})"/ ', $html, $matches) || preg_match('/"externalId":"(UC[A-Za-z0-9_-]{22})"/ ', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<meta itemprop="channelId" content="(UC[A-Za-z0-9_-]{22})">/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Extract stream description from YouTube HTML.
     */
    public function extractDescription(string $html): string
    {
        if (preg_match('/"shortDescription":\s*"((?:[^"\\\\]|\\\\.)*)"/s', $html, $matches)) {
            $decoded = json_decode('"' . $matches[1] . '"');
            if (is_string($decoded) && trim($decoded) !== '') {
                return trim($decoded);
            }
            $stripped = stripcslashes($matches[1]);
            if (trim($stripped) !== '') {
                return trim($stripped);
            }
        }

        if (preg_match('/"attributedDescription":\s*\{\s*"content":\s*"((?:[^"\\\\]|\\\\.)*)"/s', $html, $matches)) {
            $decoded = json_decode('"' . $matches[1] . '"');
            if (is_string($decoded) && trim($decoded) !== '') {
                return trim($decoded);
            }
            $stripped = stripcslashes($matches[1]);
            if (trim($stripped) !== '') {
                return trim($stripped);
            }
        }

        if (preg_match('/<meta name="description" content="([^"]*)">/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    /**
     * Extract viewers count from YouTube HTML.
     */
    private function extractViewersCount(string $html): int
    {
        if (preg_match('/"viewCount":\s*\{\s*"videoViewCountRenderer":\s*\{\s*"viewCount":\s*\{\s*"runs":\s*\[\s*\{\s*"text":\s*"([^"]+)"/i', $html, $matches)) {
            return (int) preg_replace('/[^\d]/', '', $matches[1]);
        }
        if (preg_match('/"viewCount":\s*\{\s*"videoViewCountRenderer":\s*\{\s*"viewCount":\s*\{\s*"simpleText":\s*"([^"]+)"/i', $html, $matches)) {
            return (int) preg_replace('/[^\d]/', '', $matches[1]);
        }
        if (preg_match('/"videoDetails":\s*\{.*?"viewCount":\s*"(\d+)"/s', $html, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/"viewCount":"(\d+)"/', $html, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/"originalViewCount":"(\d+)"/', $html, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
}
