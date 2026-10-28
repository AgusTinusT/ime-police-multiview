<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Officer;
use App\Models\OfficerChangeRequest;
use App\Models\Rank;
use App\Services\AuditLogger;
use App\Services\BioProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficerChangeRequestController extends Controller
{
    /**
     * Officer: List own change requests (PRD v3 T-17).
     */
    public function myRequests(): JsonResponse
    {
        $user = Auth::user();
        $officer = $user->officer;

        if (!$officer) {
            return response()->json(['message' => 'Anda belum terikat ke profil perwira.'], 403);
        }

        $requests = OfficerChangeRequest::where('officer_id', $officer->id)
            ->latest()
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }

    /**
     * Submit a new rank or agency change request (PRD v3 T-17).
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $officer = $user->officer;

        if (!$officer) {
            return response()->json(['message' => 'Anda belum terikat ke profil perwira.'], 403);
        }

        if ($officer->bioProfile?->is_suspended) {
            return response()->json([
                'message' => 'Profil perwira terkunci karena halaman bio sedang dibekukan.',
            ], 423);
        }

        $validated = $request->validate([
            'field' => 'required|string|in:rank_id,agency_id',
            'new_value' => 'required|string|max:150',
            'reason' => 'required|string|min:3|max:255',
        ]);

        $field = $validated['field'];
        $newValue = $validated['new_value'];

        // Check if an existing pending request exists for this field
        $existing = OfficerChangeRequest::where('officer_id', $officer->id)
            ->where('field', $field)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'field' => 'Anda sudah memiliki pengajuan yang sedang menunggu persetujuan Admin untuk bagian ini.',
            ]);
        }

        $oldValue = null;

        if ($field === 'rank_id') {
            $rank = is_numeric($newValue)
                ? Rank::find((int) $newValue)
                : Rank::where('rank_title', $newValue)->first();

            if (!$rank) {
                throw ValidationException::withMessages(['new_value' => 'Pangkat tidak ditemukan.']);
            }

            if ($officer->rank_id && (int) $rank->id === (int) $officer->rank_id) {
                throw ValidationException::withMessages([
                    'new_value' => 'Pangkat yang diajukan tidak boleh sama dengan pangkat saat ini.',
                ]);
            }

            // Check if rank.agency_id matches current agency OR pending agency_id request
            $currentAgencyId = $officer->agency_id;
            $pendingAgencyRequest = OfficerChangeRequest::where('officer_id', $officer->id)
                ->where('field', 'agency_id')
                ->where('status', 'pending')
                ->first();
            $allowedAgencyId = $pendingAgencyRequest ? (int) $pendingAgencyRequest->new_value : $currentAgencyId;

            if ($allowedAgencyId && $rank->agency_id && (int) $rank->agency_id !== (int) $allowedAgencyId) {
                throw ValidationException::withMessages([
                    'new_value' => "Pangkat '{$rank->rank_title}' tidak termasuk dalam kesatuan/agency perwira saat ini.",
                ]);
            }

            // Store old_value strictly from ID column (nullable) so legacy officers without rank_id compare correctly
            $oldValue = $officer->rank_id ? (string) $officer->rank_id : null;
            $newValue = (string) $rank->id;
        } elseif ($field === 'agency_id') {
            $agency = is_numeric($newValue)
                ? Agency::find((int) $newValue)
                : Agency::where('agency_code', $newValue)->first();

            if (!$agency) {
                throw ValidationException::withMessages(['new_value' => 'Kesatuan tidak ditemukan.']);
            }

            if ($officer->agency_id && (int) $agency->id === (int) $officer->agency_id) {
                throw ValidationException::withMessages([
                    'new_value' => 'Kesatuan yang diajukan tidak boleh sama dengan kesatuan saat ini.',
                ]);
            }

            // Store old_value strictly from ID column (nullable)
            $oldValue = $officer->agency_id ? (string) $officer->agency_id : null;
            $newValue = (string) $agency->id;
        }

        $changeRequest = OfficerChangeRequest::create([
            'officer_id' => $officer->id,
            'requested_by' => $user->id,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        AuditLogger::log(
            'officer.change_requested',
            'officer_change_request',
            $changeRequest->id,
            null,
            ['officer_id' => $officer->id, 'field' => $field, 'new_value' => $newValue],
            $validated['reason']
        );

        return response()->json([
            'message' => 'Pengajuan perubahan berhasil dikirim ke antrean Komando Admin.',
            'request' => $changeRequest->load('officer'),
            'change_request' => $changeRequest,
        ], 201);
    }

    /**
     * Cancel officer's own pending change request.
     */
    public function destroy(OfficerChangeRequest $changeRequest): JsonResponse
    {
        if ($changeRequest->requested_by !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Hanya pengajuan pending yang dapat dibatalkan.'], 422);
        }

        $changeRequest->update([
            'status' => 'cancelled',
            'pending_lock' => null,
            'review_reason' => 'Dibatalkan oleh perwira pemohon.',
        ]);

        AuditLogger::log(
            'officer.change_cancelled',
            'officer_change_request',
            $changeRequest->id,
            ['status' => 'pending'],
            ['status' => 'cancelled'],
            'Perwira membatalkan pengajuan perubahan'
        );

        return response()->json(['message' => 'Pengajuan perubahan telah dibatalkan.']);
    }

    /**
     * Admin: List change requests with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OfficerChangeRequest::with(['officer.agencyRelation', 'officer.rankRelation', 'requester', 'reviewer'])
            ->latest();

        if ($request->input('status') === 'all') {
            // No status filter: return all
        } elseif ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        return response()->json([
            'requests' => $query->paginate(20),
        ]);
    }

    /**
     * Admin: Approve change request and apply to officer in transaction.
     */
    public function approve(Request $request, OfficerChangeRequest $changeRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan ini sudah tidak berstatus pending.'], 422);
        }

        return DB::transaction(function () use ($changeRequest, $request) {
            $lockedRequest = OfficerChangeRequest::where('id', $changeRequest->id)->lockForUpdate()->firstOrFail();
            $officer = Officer::where('id', $lockedRequest->officer_id)->lockForUpdate()->firstOrFail();

            // Stale check: compare current officer ID value with old_value (both are strings or null)
            $currentValue = $lockedRequest->field === 'rank_id'
                ? ($officer->rank_id ? (string) $officer->rank_id : null)
                : ($officer->agency_id ? (string) $officer->agency_id : null);

            if ($currentValue !== $lockedRequest->old_value) {
                $lockedRequest->update([
                    'status' => 'rejected',
                    'pending_lock' => null,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'review_reason' => 'Pengajuan usang: data sudah berubah',
                ]);

                return response()->json([
                    'message' => 'Pengajuan ditolak otomatis karena data perwira sudah diubah sebelumnya (Stale).',
                ], 422);
            }

            $beforeState = [
                'rank_id' => $officer->rank_id,
                'rank' => $officer->rank,
                'agency_id' => $officer->agency_id,
                'department' => $officer->department,
            ];

            $messageNotice = '';

            // Apply changes
            if ($lockedRequest->field === 'rank_id') {
                $rank = is_numeric($lockedRequest->new_value)
                    ? Rank::find((int) $lockedRequest->new_value)
                    : Rank::where('rank_title', $lockedRequest->new_value)->first();

                if ($rank) {
                    $officer->rank_id = $rank->id;
                    $officer->rank = $rank->rank_title;
                }
            } elseif ($lockedRequest->field === 'agency_id') {
                $agency = is_numeric($lockedRequest->new_value)
                    ? Agency::find((int) $lockedRequest->new_value)
                    : Agency::where('agency_code', $lockedRequest->new_value)->first();

                if ($agency) {
                    $officer->agency_id = $agency->id;
                    $officer->department = $agency->agency_code;

                    // If current rank doesn't belong to the new agency, reset rank
                    if ($officer->rank_id) {
                        $currentRank = Rank::find($officer->rank_id);
                        if ($currentRank && $currentRank->agency_id && (int) $currentRank->agency_id !== (int) $agency->id) {
                            $officer->rank_id = null;
                            $officer->rank = 'Officer';
                            $messageNotice = ' Pangkat sebelumnya di-reset karena tidak sesuai dengan agency baru.';
                        }
                    }
                }
            }

            $officer->owner_updated_at = now();
            $officer->save();

            if ($officer->bioProfile) {
                BioProfileService::invalidatePublicCache($officer->bioProfile->slug);
            }

            $reviewReason = $request->reason ?? 'Disetujui oleh Komando Admin';
            $lockedRequest->update([
                'status' => 'approved',
                'pending_lock' => null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_reason' => $reviewReason,
            ]);

            AuditLogger::log(
                'officer.change_approved',
                'officer_change_request',
                $lockedRequest->id,
                $beforeState,
                [
                    'status' => 'approved',
                    'officer_id' => $officer->id,
                    'field' => $lockedRequest->field,
                    'new_value' => $lockedRequest->new_value,
                ],
                $reviewReason
            );

            return response()->json([
                'message' => "Pengajuan perubahan {$lockedRequest->field} untuk {$officer->officer_name} berhasil disetujui.{$messageNotice}",
                'request' => $lockedRequest->load(['officer', 'requester', 'reviewer']),
            ]);
        });
    }

    /**
     * Admin: Reject change request.
     */
    public function reject(Request $request, OfficerChangeRequest $changeRequest): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        if ($changeRequest->status !== 'pending') {
            return response()->json(['message' => 'Pengajuan ini sudah tidak berstatus pending.'], 422);
        }

        $changeRequest->update([
            'status' => 'rejected',
            'pending_lock' => null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_reason' => $request->reason,
        ]);

        AuditLogger::log(
            'officer.change_rejected',
            'officer_change_request',
            $changeRequest->id,
            ['status' => 'pending'],
            ['status' => 'rejected'],
            $request->reason
        );

        return response()->json([
            'message' => 'Pengajuan perubahan berhasil ditolak.',
            'request' => $changeRequest->load(['officer', 'requester', 'reviewer']),
        ]);
    }
}
