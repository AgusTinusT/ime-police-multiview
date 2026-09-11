<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TacChannel;
use App\Models\ActiveStream;

class TacChannelController extends Controller
{
    /**
     * Get list of all TAC channels and their current active states.
     */
    public function index()
    {
        TacChannel::ensureChannelsExist();

        $channels = TacChannel::orderBy('id')->get()->map(function ($ch) {
            $ch->checkAndResetIfExpired();
            return [
                'id' => $ch->id,
                'code' => $ch->code,
                'name' => $ch->name,
                'video_ids' => $ch->video_ids ?? [],
                'expires_at' => $ch->expires_at ? $ch->expires_at->toIso8601String() : null,
                'remaining_seconds' => $ch->remaining_seconds,
                'is_active' => $ch->is_active,
                'unit_count' => $ch->unit_count,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $channels,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Assign a video stream to a specific TAC radio channel (1-click).
     */
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'tac_code' => 'required|string|in:TAC_1,TAC_2,TAC_3,TAC_4,TAC_5,TAC_6,TAC_7,TAC_8,TAC_9,TAC_10',
            'video_id' => 'required|string|max:32',
        ]);

        $tacCode = $validated['tac_code'];
        $videoId = trim($validated['video_id']);

        TacChannel::ensureChannelsExist();

        // 1. Remove video_id from all other TAC channels to prevent duplicates
        $otherChannels = TacChannel::where('code', '!=', $tacCode)->get();
        foreach ($otherChannels as $otherCh) {
            $otherIds = $otherCh->video_ids ?? [];
            if (in_array($videoId, $otherIds)) {
                $otherIds = array_values(array_diff($otherIds, [$videoId]));
                $otherCh->video_ids = $otherIds;
                if (empty($otherIds)) {
                    $otherCh->expires_at = null;
                }
                $otherCh->save();
            }
        }

        // 2. Assign to target TAC channel
        $targetCh = TacChannel::where('code', $tacCode)->first();
        if (!$targetCh) {
            return response()->json([
                'status' => 'error',
                'message' => 'TAC Channel tidak ditemukan.',
            ], 404);
        }

        $currentIds = $targetCh->video_ids ?? [];
        
        // If channel was idle/empty or expired, start fresh 30-minute situation timer
        if (empty($currentIds) || !$targetCh->expires_at || now()->isAfter($targetCh->expires_at)) {
            $targetCh->expires_at = now()->addMinutes(30);
        }

        if (!in_array($videoId, $currentIds)) {
            $currentIds[] = $videoId;
        }
        $targetCh->video_ids = array_values($currentIds);
        $targetCh->save();

        return $this->index();
    }

    /**
     * Remove a stream from TAC channel.
     */
    public function remove(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'required|string|max:32',
            'tac_code' => 'nullable|string|in:TAC_1,TAC_2,TAC_3,TAC_4,TAC_5,TAC_6,TAC_7,TAC_8,TAC_9,TAC_10',
        ]);

        $videoId = trim($validated['video_id']);
        $tacCode = $validated['tac_code'] ?? null;

        $query = TacChannel::query();
        if ($tacCode) {
            $query->where('code', $tacCode);
        }

        $channels = $query->get();
        foreach ($channels as $ch) {
            $ids = $ch->video_ids ?? [];
            if (in_array($videoId, $ids)) {
                $ids = array_values(array_diff($ids, [$videoId]));
                $ch->video_ids = $ids;
                if (empty($ids)) {
                    $ch->expires_at = null;
                }
                $ch->save();
            }
        }

        return $this->index();
    }

    /**
     * Extend situation timer by +20 minutes (or specified duration).
     */
    public function extend(Request $request)
    {
        $validated = $request->validate([
            'tac_code' => 'required|string|in:TAC_1,TAC_2,TAC_3,TAC_4,TAC_5,TAC_6,TAC_7,TAC_8,TAC_9,TAC_10',
            'minutes' => 'nullable|integer|min:5|max:60',
        ]);

        $tacCode = $validated['tac_code'];
        $minutes = (int) ($validated['minutes'] ?? 20);

        $channel = TacChannel::where('code', $tacCode)->first();
        if (!$channel) {
            return response()->json([
                'status' => 'error',
                'message' => 'TAC Channel tidak ditemukan.',
            ], 404);
        }

        $maxExpiry = now()->addMinutes(45);
        if (!$channel->expires_at || now()->isAfter($channel->expires_at)) {
            $newExpiry = now()->addMinutes($minutes);
        } else {
            $newExpiry = $channel->expires_at->copy()->addMinutes($minutes);
        }

        if ($newExpiry->isAfter($maxExpiry)) {
            $newExpiry = $maxExpiry;
        }

        $channel->expires_at = $newExpiry;
        $channel->save();

        return $this->index();
    }

    /**
     * Disband / clear a TAC channel immediately.
     */
    public function clear(Request $request)
    {
        $validated = $request->validate([
            'tac_code' => 'required|string|in:TAC_1,TAC_2,TAC_3,TAC_4,TAC_5,TAC_6,TAC_7,TAC_8,TAC_9,TAC_10',
        ]);

        $tacCode = $validated['tac_code'];
        $channel = TacChannel::where('code', $tacCode)->first();
        if ($channel) {
            $channel->video_ids = [];
            $channel->expires_at = null;
            $channel->save();
        }

        return $this->index();
    }
}
