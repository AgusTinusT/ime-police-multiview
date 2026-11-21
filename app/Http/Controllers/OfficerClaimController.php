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

        if (!$officer->is_active) {
            throw ValidationException::withMessages([
                'officer_id' => 'Profil perwira ini berstatus non-aktif sehingga tidak dapat diklaim.',
            ]);
        }

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
        $officers = Officer::roster()
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
     * Create an automated YouTube token claim (PRD v3 T-14).
     */
    public function createYouTubeClaim(Request $request): JsonResponse
    {
        $request->validate([
            'officer_id' => 'required|exists:officers,id',
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

        // Throttle: max 3 tokens created per 24 hours per user
        $recentTokenCount = OfficerClaim::where('user_id', $user->id)
            ->where('verification_method', 'youtube_token')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentTokenCount >= 3) {
            throw ValidationException::withMessages([
                'officer_id' => 'Batas penerbitan token klaim tercapai (maksimal 3 per 24 jam). Silakan gunakan token yang sudah dibuat atau coba lagi nanti.',
            ]);
        }

        // Check if user already has an active pending claim
        $existingUserClaim = OfficerClaim::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingUserClaim) {
            if ($existingUserClaim->officer_id === $request->officer_id && $existingUserClaim->verification_method === 'youtube_token') {
                $token = \App\Services\YouTubeChannelVerifier::generateToken();
                $tokenHash = hash('sha256', $token);
                $existingUserClaim->update([
                    'verification_token_hash' => $tokenHash,
                    'token_expires_at' => now()->addHours(24),
                ]);

                return response()->json([
                    'status' => 'pending',
                    'message' => 'Token verifikasi baru berhasil diterbitkan.',
                    'claim' => $existingUserClaim->load('officer'),
                    'claim_id' => $existingUserClaim->id,
                    'token' => $token,
                    'channel_url' => "https://www.youtube.com/channel/{$existingUserClaim->officer->channel_id}",
                    'expires_at' => $existingUserClaim->token_expires_at->toIso8601String(),
                ], 201);
            }

            throw ValidationException::withMessages([
                'officer_id' => 'Anda sudah memiliki pengajuan klaim perwira yang sedang berjalan.',
            ]);
        }

        $officer = Officer::findOrFail($request->officer_id);

        if (!$officer->is_active) {
            throw ValidationException::withMessages([
                'officer_id' => 'Profil perwira ini berstatus non-aktif sehingga tidak dapat diklaim.',
            ]);
        }

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

        // Generate high entropy verification token (80 bits)
        $token = \App\Services\YouTubeChannelVerifier::generateToken();
        $tokenHash = hash('sha256', $token);

        $claim = OfficerClaim::create([
            'user_id' => $user->id,
            'officer_id' => $officer->id,
            'verification_method' => 'youtube_token',
            'verification_token_hash' => $tokenHash,
            'token_expires_at' => now()->addHours(24),
            'status' => 'pending',
        ]);

        AuditLogger::log(
            'claim.token_created',
            'officer_claim',
            $claim->id,
            null,
            ['officer_id' => $officer->id],
            'User created YouTube verification token claim'
        );

        return response()->json([
            'status' => 'pending',
            'message' => 'Token verifikasi berhasil dibuat. Tempelkan token ini di deskripsi channel YouTube Anda lalu klik Periksa Sekarang.',
            'claim' => $claim->load('officer'),
            'claim_id' => $claim->id,
            'token' => $token, // Displayed once to user
            'channel_url' => "https://www.youtube.com/channel/{$officer->channel_id}",
            'expires_at' => $claim->token_expires_at->toIso8601String(),
        ], 201);
    }

    /**
     * Check YouTube channel description for token and auto-approve claim (PRD v3 T-14).
     */
    public function verifyYouTubeClaim(Request $request, OfficerClaim $claim, \App\Services\YouTubeChannelVerifier $verifier): JsonResponse
    {
        if ($claim->user_id !== Auth::id()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($claim->verification_method !== 'youtube_token') {
            return response()->json(['message' => 'Pengajuan ini bukan tipe verifikasi YouTube.'], 422);
        }

        if ($claim->status !== 'pending') {
            return response()->json(['message' => "Pengajuan sudah berstatus '{$claim->status}'."], 422);
        }

        // Check expiration
        if ($claim->token_expires_at && $claim->token_expires_at->isPast()) {
            $claim->update(['status' => 'expired']);
            return response()->json(['message' => 'Token verifikasi telah kedaluwarsa (berlaku 24 jam). Silakan buat token baru.'], 422);
        }

        // Throttle: max 5 checks per hour
        if ($claim->check_attempts >= 5 && $claim->last_checked_at && $claim->last_checked_at->diffInMinutes(now()) < 60) {
            return response()->json(['message' => 'Batas percobaan pemeriksaan tercapai (maksimal 5 kali per jam). Silakan tunggu sebelum mencoba lagi.'], 429);
        }

        $officer = $claim->officer;
        if (!$officer || !$officer->channel_id) {
            return response()->json(['message' => 'Data channel YouTube perwira tidak ditemukan di master data.'], 422);
        }

        // Verify token in YouTube channel description using channel_id
        $foundHashes = $verifier->findTokenHashes($officer->channel_id);
        $claimHash = strtolower($claim->verification_token_hash);
        $matched = in_array($claimHash, array_map('strtolower', $foundHashes), true);

        if (!$matched) {
            $claim->increment('check_attempts');
            $claim->update(['last_checked_at' => now()]);

            return response()->json([
                'message' => 'Kode belum ditemukan di deskripsi channel. Pastikan sudah disimpan, lalu coba lagi dalam beberapa menit.',
                'check_attempts' => $claim->check_attempts,
            ], 422);
        }

        // Token verified! Bind officer inside transaction
        return DB::transaction(function () use ($claim, $officer) {
            $user = User::findOrFail($claim->user_id);
            [$lockedOfficer, $profile] = \App\Services\OfficerBindingService::bind(
                $officer,
                $user,
                'Verifikasi otomatis YouTube',
                null
            );

            $claim->status = 'approved';
            $claim->reviewed_by = null;
            $claim->reviewed_at = now();
            $claim->review_reason = 'Verifikasi otomatis YouTube';
            $claim->save();

            AuditLogger::log(
                'claim.auto_approved',
                'officer_claim',
                $claim->id,
                ['status' => 'pending'],
                ['status' => 'approved', 'officer_id' => $lockedOfficer->id, 'user_id' => $user->id],
                $claim->review_reason
            );

            return response()->json([
                'message' => "Verifikasi berhasil! Anda kini resmi menjadi pengelola profil {$lockedOfficer->officer_name}.",
                'claim' => $claim->load(['officer', 'user']),
                'officer' => $lockedOfficer->load(['bioProfile']),
            ]);
        });
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
            $officer = Officer::where('id', $claim->officer_id)->firstOrFail();
            $user = User::findOrFail($claim->user_id);

            $reviewReason = $request->reason ?? 'Disetujui oleh Admin SASP';
            [$lockedOfficer, $profile] = \App\Services\OfficerBindingService::bind($officer, $user, $reviewReason, Auth::id());

            // Mark claim approved
            $claim->status = 'approved';
            $claim->reviewed_by = Auth::id();
            $claim->reviewed_at = now();
            $claim->review_reason = $reviewReason;
            $claim->save();

            AuditLogger::log(
                'claim.approved',
                'officer_claim',
                $claim->id,
                ['status' => 'pending'],
                ['status' => 'approved', 'officer_id' => $lockedOfficer->id, 'user_id' => $user->id],
                $claim->review_reason
            );

            return response()->json([
                'message' => "Klaim berhasil disetujui. {$lockedOfficer->officer_name} kini terikat ke akun {$user->name}.",
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
