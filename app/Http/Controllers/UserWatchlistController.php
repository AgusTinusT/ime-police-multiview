<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserWatchlist;
use Illuminate\Support\Facades\Auth;

class UserWatchlistController extends Controller
{
    /**
     * Get user's cloud watchlist stream IDs.
     */
    public function index()
    {
        $userId = Auth::id();
        $watchlists = UserWatchlist::where('user_id', $userId)
            ->pluck('video_id');

        return response()->json([
            'status' => 'success',
            'video_ids' => $watchlists,
        ]);
    }

    /**
     * Toggle a stream in user's cloud watchlist.
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'required|string|max:64',
            'officer_name' => 'nullable|string|max:150',
            'channel_id' => 'nullable|string|max:64',
        ]);

        $userId = Auth::id();
        $videoId = $validated['video_id'];

        $existing = UserWatchlist::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            // Maximum 6 personal streams limit
            $count = UserWatchlist::where('user_id', $userId)->count();
            if ($count >= 6) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Personal Watchlist maksimal 6 stream.',
                ], 422);
            }

            UserWatchlist::create([
                'user_id' => $userId,
                'video_id' => $videoId,
                'officer_name' => $validated['officer_name'] ?? null,
                'channel_id' => $validated['channel_id'] ?? null,
            ]);
            $action = 'added';
        }

        $allVideoIds = UserWatchlist::where('user_id', $userId)->pluck('video_id');

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'video_ids' => $allVideoIds,
        ]);
    }
}
