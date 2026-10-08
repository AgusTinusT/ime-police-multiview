<?php

namespace App\Http\Controllers;

use App\Models\ClipperRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClipperRequestController extends Controller
{
    /**
     * Get the current user's latest request status.
     */
    public function userStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => null]);
        }

        $latestRequest = ClipperRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => $latestRequest ? $latestRequest->status : null,
            'request' => $latestRequest,
        ]);
    }

    /**
     * Submit a new request for Clipper role.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->canTrimVideo()) {
            return response()->json([
                'message' => 'Akun Anda sudah memiliki akses Clipper / Admin.'
            ], 400);
        }

        $existingPending = ClipperRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Anda sudah memiliki pengajuan akses Clipper yang sedang diproses.'
            ], 422);
        }

        $validated = $request->validate([
            'request_type' => 'required|string|in:officer,creator,vip,general',
            'channel_url' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:500',
        ]);

        $clipperRequest = ClipperRequest::create([
            'user_id' => $user->id,
            'request_type' => $validated['request_type'],
            'channel_url' => $validated['channel_url'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan hak spesial Clipper berhasil dikirim dan menunggu review Admin.',
            'request' => $clipperRequest,
        ], 201);
    }

    /**
     * Admin: List all clipper requests.
     */
    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $requests = ClipperRequest::with('user:id,name,email,role,clipper_tier')
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    /**
     * Admin: Approve a clipper request and grant clipper role to user.
     */
    public function approve(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $clipperReq = ClipperRequest::findOrFail($id);
        $clipperReq->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        // Promote user role to 'clipper' and assign corresponding tier
        $user = User::findOrFail($clipperReq->user_id);
        if ($user->role !== 'admin') {
            $user->role = 'clipper';
            $user->clipper_tier = ($clipperReq->request_type === 'creator') ? 'creator' : 'ultimate';
            $user->save();
        }

        return response()->json([
            'message' => "Pengajuan dari {$user->name} berhasil disetujui ({$user->getClipperTierName()}).",
            'request' => $clipperReq,
        ]);
    }

    /**
     * Admin: Reject a clipper request.
     */
    public function reject(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $clipperReq = ClipperRequest::findOrFail($id);
        $clipperReq->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Pengajuan akses Clipper telah ditolak.',
            'request' => $clipperReq,
        ]);
    }
}
