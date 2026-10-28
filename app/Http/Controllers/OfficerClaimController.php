<?php

namespace App\Http\Controllers;

use App\Models\Officer;
use App\Models\OfficerClaim;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BioProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficerClaimController extends Controller
{
    /**
     * Submit a claim request for an unclaimed officer profile (by normal user/streamer).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'officer_id' => 'required|exists:officers,id',
            'proof_discord_tag' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        if ($user->isBanned()) {
            return response()->json(['message' => 'Akun Anda berstatus banned.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Anda wajib memverifikasi email sebelum mengajukan klaim perwira.'], 403);
        }

        // Check if user is already bound to an officer
        if ($user->officer()->exists()) {
            throw ValidationException::withMessages([
                'officer_id' => 'Akun Anda sudah terhubung dengan perwira ' . $user->officer->officer_name,
            ]);
        }

        // Check if user already has an active pending claim
        $existingUserClaim = OfficerClaim::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingUserClaim) {
            throw ValidationException::withMessages([
                'officer_id' => 'Anda sudah memiliki pengajuan klaim perwira yang sedang ditinjau Admin.',
            ]);
        }

        $officer = Officer::findOrFail($request->officer_id);

        if ($officer->user_id !== null) {
            throw ValidationException::withMessages([
                'officer_id' => 'Profil perwira ini sudah diklaim dan terikat ke akun lain.',
            ]);
        }

        // Check if officer already has another pending claim
        $existingOfficerClaim = OfficerClaim::where('officer_id', $officer->id)
            ->where('status', 'pending')
            ->first();

        if ($existingOfficerClaim) {
            throw ValidationException::withMessages([
                'officer_id' => 'Perwira ini sedang dalam proses peninjauan klaim lain. Silakan hubungi Admin.',
            ]);
        }

        $claim = OfficerClaim::create([
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'verification_method' => 'manual_admin',
            'proof_discord_tag' => $request->proof_discord_tag,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        AuditLogger::log(
            'claim.submitted',
            'officer_claim',
            $claim->id,
            null,
            ['officer_id' => $officer->id, 'discord' => $request->proof_discord_tag],
            'User submitted manual officer verification claim'
        );

        return response()->json([
            'message' => 'Pengajuan klaim perwira berhasil dikirim. Komando Admin akan meninjau Discord dan kanal Anda.',
            'claim' => $claim->load('officer'),
        ], 201);
    }

    /**
     * Cancel user's own pending claim.
     */
    public function destroy(OfficerClaim $claim): JsonResponse
    {
        if ($claim->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($claim->status !== 'pending') {
            return response()->json(['message' => 'Hanya pengajuan berstatus pending yang dapat dibatalkan.'], 422);
        }

        $claim->status = 'cancelled';
        $claim->save();

        AuditLogger::log('claim.cancelled', 'officer_claim', $claim->id, null, ['status' => 'cancelled'], 'User cancelled claim');

        return response()->json(['message' => 'Pengajuan klaim perwira telah dibatalkan.']);
    }

    /**
     * Get claims submitted by the authenticated user (T-07).
     */
    public function myClaims(Request $request): JsonResponse
    {
        $claims = OfficerClaim::with('officer')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'claims' => $claims,
        ]);
    }

    /**
     * Get active officers that are not yet claimed/bound (T-07).
     */
    public function unclaimedOfficers(): JsonResponse
    {
        $officers = Officer::where('is_active', true)
            ->whereNull('user_id')
            ->orderBy('callsign')
            ->get(['id', 'officer_name', 'streamer_name', 'callsign', 'department', 'rank', 'avatar_url']);

        return response()->json([
            'officers' => $officers,
        ]);
    }

    /**
     * Admin: List claim applications with filter.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $query = OfficerClaim::with(['user', 'officer', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json([
            'claims' => $query->paginate(20),
        ]);
    }

    /**
     * Admin: Approve claim and bind officer.
     */
    public function approve(Request $request, OfficerClaim $claim): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if ($claim->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan ini sudah tidak berstatus pending.'], 422);
        }

        return DB::transaction(function () use ($claim, $request) {
            $officer = Officer::where('id', $claim->officer_id)->lockForUpdate()->firstOrFail();
            $user = User::findOrFail($claim->user_id);

            if ($officer->user_id) {
                return response()->json(['message' => 'Perwira ini telah terikat ke akun lain sebelumnya. Lakukan unbind terlebih dahulu.'], 422);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'User belum memverifikasi alamat email.'], 422);
            }

            // Bind officer
            $officer->user_id = $user->id;
            $officer->save();

            // Ensure bio profile exists
            $profile = BioProfileService::ensureBioProfile($officer);
            $profile->is_active = true;
            $profile->save();

            // Mark claim approved
            $claim->status = 'approved';
            $claim->reviewed_by = Auth::id();
            $claim->reviewed_at = now();
            $claim->review_reason = $request->reason ?? 'Disetujui oleh Admin SASP';
            $claim->save();

            AuditLogger::log(
                'claim.approved',
                'officer_claim',
                $claim->id,
                ['status' => 'pending'],
                ['status' => 'approved', 'officer_id' => $officer->id, 'user_id' => $user->id],
                $claim->review_reason
            );

            return response()->json([
                'message' => "Klaim berhasil disetujui. {$officer->officer_name} kini terikat ke akun {$user->name}.",
                'claim' => $claim->load(['user', 'officer']),
            ]);
        });
    }

    /**
     * Admin: Reject claim application.
     */
    public function reject(Request $request, OfficerClaim $claim): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        if ($claim->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan ini sudah tidak berstatus pending.'], 422);
        }

        $claim->status = 'rejected';
        $claim->reviewed_by = Auth::id();
        $claim->reviewed_at = now();
        $claim->review_reason = $request->reason;
        $claim->save();

        AuditLogger::log(
            'claim.rejected',
            'officer_claim',
            $claim->id,
            ['status' => 'pending'],
            ['status' => 'rejected'],
            $request->reason
        );

        return response()->json([
            'message' => 'Pengajuan klaim perwira telah ditolak.',
            'claim' => $claim->load(['user', 'officer']),
        ]);
    }
}
