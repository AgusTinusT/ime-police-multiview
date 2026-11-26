<?php

namespace App\Http\Controllers;

use App\Services\LiveLatencyAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLiveLatencyController extends Controller
{
    public function index(Request $request, LiveLatencyAnalyticsService $analytics): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        if (! in_array($days, [0, 1, 7, 30, 90], true)) {
            $days = 7;
        }

        $source = trim((string) $request->query('source', 'all'));
        $allowedSources = ['all', 'websub', 'websub_recheck', 'sync_hashtag', 'sync_rss', 'sync_handle', 'sync'];
        if (! in_array($source, $allowedSources, true)) {
            $source = 'all';
        }

        $limit = max(1, min(100, (int) $request->query('limit', 50)));

        return response()->json($analytics->buildReport($days, $source, $limit));
    }
}
