<?php

namespace App\Http\Controllers;

use App\Models\ClipperDelegation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClipperDelegationController extends Controller
{
    /**
     * Get active delegations assigned by current user (max 2 slots).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->canDelegateClipper()) {
            return response()->json([
                'can_delegate' => false,
                'max_slots' => 2,
                'used_slots' => 0,
                'delegations' => [],
            ]);
        }

        $delegations = ClipperDelegation::with('assignedUser:id,name,email,role,clipper_tier')
            ->where('granted_by_user_id', $user->id)
            ->get();

        return response()->json([
            'can_delegate' => true,
            'max_slots' => 2,
            'used_slots' => $delegations->count(),
            'remaining_slots' => max(0, 2 - $delegations->count()),
            'delegations' => $delegations,
        ]);
    }

    /**
     * Assign a member account as Clipper (Consume 1 slot out of 2).
     */
    public function assign(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->canDelegateClipper()) {
            return response()->json([
                'message' => 'Hanya Officer / Streamer Kedinasan dan Admin yang memiliki hak penunjukan Clipper.'
            ], 403);
        }

        $currentCount = ClipperDelegation::where('granted_by_user_id', $user->id)->count();
        if ($currentCount >= 2) {
            throw ValidationException::withMessages([
                'email' => 'Batas maksimal penunjukan penunjukan Clipper Anda (2/2 slot) telah terpakai. Cabut salah satu akses untuk mengosongkan slot.'
            ]);
        }

        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'tier' => 'nullable|string|in:creator,ultimate',
        ]);

        $targetUser = User::where('email', $validated['email'])->firstOrFail();

        if ($targetUser->id === $user->id) {
            throw ValidationException::withMessages([
                'email' => 'Anda tidak dapat mendelegasikan hak penunjukan ke akun sendiri.'
            ]);
        }

        if ($targetUser->isAdmin()) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna ini sudah memiliki hak Admin.'
            ]);
        }

        // Check if user is already delegated by someone else
        $existingDelegation = ClipperDelegation::where('assigned_user_id', $targetUser->id)->first();
        if ($existingDelegation) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna ini sudah ditunjuk sebagai Clipper oleh Officer/Admin lain.'
            ]);
        }

        $tierToAssign = $validated['tier'] ?? 'creator';

        $delegation = ClipperDelegation::create([
            'granted_by_user_id' => $user->id,
            'assigned_user_id' => $targetUser->id,
            'tier' => $tierToAssign,
        ]);

        // Upgrade target user role & tier
        $targetUser->role = 'clipper';
        $targetUser->clipper_tier = $tierToAssign;
        $targetUser->save();

        return response()->json([
            'message' => "Berhasil menunjuk {$targetUser->name} ({$targetUser->email}) sebagai {$targetUser->getClipperTierName()}.",
            'delegation' => $delegation->load('assignedUser'),
            'used_slots' => $currentCount + 1,
            'remaining_slots' => max(0, 1 - $currentCount),
        ], 201);
    }

    /**
     * Revoke Clipper assignment (Free up 1 slot out of 2).
     */
    public function revoke(Request $request, $id)
    {
        $user = $request->user();

        $delegation = ClipperDelegation::findOrFail($id);

        if (!$user->isAdmin() && $delegation->granted_by_user_id !== $user->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak untuk mencabut penunjukan ini.'
            ], 403);
        }

        $targetUser = User::find($delegation->assigned_user_id);
        if ($targetUser && !$targetUser->isAdmin()) {
            $targetUser->role = 'member';
            $targetUser->clipper_tier = 'free';
            $targetUser->save();
        }

        $delegation->delete();

        return response()->json([
            'message' => 'Penunjukan Clipper telah dicabut. 1 Slot penunjukan kembali tersedia.',
        ]);
    }
}
