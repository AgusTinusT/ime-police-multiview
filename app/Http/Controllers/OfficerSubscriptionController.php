<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\OfficerSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficerSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()
            ->officerSubscriptions()
            ->with(['officer:id,officer_name,streamer_name,callsign,department,avatar_url,avatar_hidden,is_active'])
            ->latest()
            ->get();

        return response()->json([
            'officer_ids' => $subscriptions->pluck('officer_id')->values()->all(),
            'subscriptions' => $subscriptions->map(fn (OfficerSubscription $sub) => [
                'id' => $sub->id,
                'officer_id' => $sub->officer_id,
                'notify_in_app' => $sub->notify_in_app,
                'notify_web_push' => $sub->notify_web_push,
                'notify_discord_dm' => $sub->notify_discord_dm,
                'created_at' => $sub->created_at?->toIso8601String(),
                'officer' => $sub->officer ? [
                    'id' => $sub->officer->id,
                    'officer_name' => $sub->officer->officer_name,
                    'streamer_name' => $sub->officer->streamer_name,
                    'callsign' => $sub->officer->callsign,
                    'department' => $sub->officer->department,
                    'avatar_url' => $sub->officer->public_avatar_url,
                ] : null,
            ])->values()->all(),
        ]);
    }

    public function store(Request $request, Officer $officer): JsonResponse
    {
        if (! $officer->is_active) {
            return response()->json([
                'message' => 'Perwira ini sedang tidak aktif dan tidak dapat diikuti.',
            ], 422);
        }

        $validated = $request->validate([
            'notify_in_app' => ['sometimes', 'boolean'],
            'notify_web_push' => ['sometimes', 'boolean'],
            'notify_discord_dm' => ['sometimes', 'boolean'],
        ]);

        $subscription = OfficerSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'officer_id' => $officer->id,
            ],
            [
                'notify_in_app' => $validated['notify_in_app'] ?? true,
                'notify_web_push' => $validated['notify_web_push'] ?? true,
                'notify_discord_dm' => $validated['notify_discord_dm'] ?? true,
            ],
        );

        $status = $subscription->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'message' => 'Berhasil mengikuti notifikasi live perwira.',
            'subscribed' => true,
            'subscription' => [
                'id' => $subscription->id,
                'officer_id' => $subscription->officer_id,
                'notify_in_app' => $subscription->notify_in_app,
                'notify_web_push' => $subscription->notify_web_push,
                'notify_discord_dm' => $subscription->notify_discord_dm,
            ],
            'subscribers_count' => $officer->subscriptions()->count(),
        ], $status);
    }

    public function destroy(Request $request, Officer $officer): JsonResponse
    {
        OfficerSubscription::where('user_id', $request->user()->id)
            ->where('officer_id', $officer->id)
            ->delete();

        return response()->json([
            'message' => 'Berhasil berhenti mengikuti notifikasi live perwira.',
            'subscribed' => false,
            'subscribers_count' => $officer->subscriptions()->count(),
        ]);
    }
}
