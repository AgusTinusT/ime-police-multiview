<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBlockedOfficerController extends Controller
{
    /**
     * Get list of officers blocked/hidden by current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $blockedOfficers = $user->blockedOfficers()
            ->select([
                'officers.id',
                'channel_id',
                'streamer_name',
                'officer_name',
                'callsign',
                'badge_number',
                'department',
                'rank',
                'avatar_url',
            ])
            ->get();

        return response()->json([
            'blocked_officer_ids' => $blockedOfficers->pluck('id')->values()->all(),
            'blocked_officers' => $blockedOfficers,
        ]);
    }

    /**
     * Block/hide an officer for the current user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'officer_id' => 'required|integer|exists:officers,id',
        ]);

        $user = $request->user();
        $user->blockedOfficers()->syncWithoutDetaching([$validated['officer_id']]);

        return response()->json([
            'message' => 'Perwira berhasil disembunyikan dari feed.',
            'blocked_officer_ids' => $user->blockedOfficers()->pluck('officers.id')->values()->all(),
        ]);
    }

    /**
     * Unblock/unhide an officer for the current user.
     */
    public function destroy(Request $request, Officer $officer): JsonResponse
    {
        $user = $request->user();
        $user->blockedOfficers()->detach($officer->id);

        return response()->json([
            'message' => 'Perwira berhasil ditampilkan kembali.',
            'blocked_officer_ids' => $user->blockedOfficers()->pluck('officers.id')->values()->all(),
        ]);
    }

    /**
     * Sync blocked officers list (e.g. from client localStorage upon login).
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'officer_ids' => 'present|array',
            'officer_ids.*' => 'integer|exists:officers,id',
        ]);

        $user = $request->user();
        if (!empty($validated['officer_ids'])) {
            $user->blockedOfficers()->syncWithoutDetaching($validated['officer_ids']);
        }

        return response()->json([
            'message' => 'Daftar perwira berhasil disinkronkan.',
            'blocked_officer_ids' => $user->blockedOfficers()->pluck('officers.id')->values()->all(),
        ]);
    }

    /**
     * Reset / unblock all hidden officers for the current user.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->blockedOfficers()->detach();

        return response()->json([
            'message' => 'Semua perwira berhasil ditampilkan kembali.',
            'blocked_officer_ids' => [],
        ]);
    }
}

