<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Officer;
use App\Services\AuditLogger;
use App\Services\BioProfileService;
use App\Services\NameSkeleton;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OfficerSelfProfileController extends Controller
{
    /**
     * Redirect legacy dashboard to unified Settings page (SET-01).
     */
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('settings', ['tab' => 'officer']);
    }

    /**
     * Build props for the Officer Settings tab (SET-01).
     */
    public static function pageProps(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        $officer = $user->officer()->with([
            'bioProfile',
            'agencyRelation',
            'rankRelation',
            'divisionRelation',
            'changeRequests' => function ($q) {
                $q->latest();
            },
        ])->first();

        if (!$officer) {
            return [];
        }

        $canChangeCallsign = true;
        $remainingCooldownDays = 0;
        if ($officer->callsign_changed_at) {
            $daysSinceChange = $officer->callsign_changed_at->diffInDays(now());
            if ($daysSinceChange < 7) {
                $canChangeCallsign = false;
                $remainingCooldownDays = 7 - $daysSinceChange;
            }
        }

        $agenciesMaster = Agency::with(['ranks', 'divisions'])->get();

        return [
            'officer' => $officer,
            'agenciesMaster' => $agenciesMaster,
            'canChangeCallsign' => $canChangeCallsign,
            'remainingCooldownDays' => $remainingCooldownDays,
            'bioSlug' => $officer->bioProfile?->slug,
        ];
    }

    /**
     * Update self-managed officer profile fields (PRD v3 4.3, 5.1).
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();
        $officer = $user->officer()->with('bioProfile')->first();

        if (!$officer) {
            return response()->json(['message' => 'Anda belum terikat ke profil perwira.'], 403);
        }

        if ($officer->bioProfile?->is_suspended) {
            return response()->json([
                'message' => 'Profil dinas terkunci karena halaman bio sedang dibekukan oleh Komando Kepolisian SASP.',
            ], 423);
        }

        // PRD T-16: Reject locked fields with explicit 422
        $lockedFields = [
            'channel_id', 'handle', 'is_active', 'duty_status',
            'bypass_hashtag_check', 'user_id', 'department', 'rank',
            'rank_id', 'agency_id', 'monthly_duty_minutes', 'last_duty_at',
        ];

        foreach ($lockedFields as $lockedField) {
            if ($request->has($lockedField)) {
                throw ValidationException::withMessages([
                    $lockedField => "Field {$lockedField} tidak dapat diubah sendiri.",
                ]);
            }
        }

        // Whitelist validation of mutable fields only
        $validated = $request->validate([
            'officer_name' => 'nullable|string|min:3|max:150',
            'streamer_name' => 'nullable|string|min:2|max:150',
            'avatar_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:500'],
            'patrol_zone' => 'nullable|string|max:150',
            'division_id' => 'nullable|exists:divisions,id',
            'callsign' => 'nullable|string|min:2|max:50',
            'badge_number' => 'nullable|string|max:50',
        ]);

        // Validate avatar host against config('biolinks.avatar_hosts')
        if (!empty($validated['avatar_url'])) {
            $host = parse_url($validated['avatar_url'], PHP_URL_HOST);
            $allowedHosts = config('biolinks.avatar_hosts', [
                'yt3.ggpht.com',
                'yt3.googleusercontent.com',
                'i.ytimg.com',
            ]);

            if (!$host || !in_array(strtolower($host), array_map('strtolower', $allowedHosts), true)) {
                throw ValidationException::withMessages([
                    'avatar_url' => 'Avatar hanya boleh berasal dari host gambar YouTube resmi (' . implode(', ', $allowedHosts) . ').',
                ]);
            }
        }

        // Validate division belongs to officer's agency
        if (!empty($validated['division_id'])) {
            $division = Division::find($validated['division_id']);
            if (!$division || ($officer->agency_id && (int) $division->agency_id !== (int) $officer->agency_id)) {
                throw ValidationException::withMessages([
                    'division_id' => 'Divisi yang dipilih bukan bagian dari kesatuan/agency perwira saat ini.',
                ]);
            }
        }

        return DB::transaction(function () use ($officer, $validated, $user, $request) {
            $lockedOfficer = Officer::where('id', $officer->id)->lockForUpdate()->firstOrFail();

            $beforeState = [
                'officer_name' => $lockedOfficer->officer_name,
                'streamer_name' => $lockedOfficer->streamer_name,
                'callsign' => $lockedOfficer->callsign,
                'badge_number' => $lockedOfficer->badge_number,
                'avatar_url' => $lockedOfficer->avatar_url,
                'patrol_zone' => $lockedOfficer->patrol_zone,
                'division_id' => $lockedOfficer->division_id,
            ];

            $updateData = [];
            $nameChanged = false;
            $callsignOrBadgeChanged = false;

            // 1. Check & validate names with NameSkeleton
            if (isset($validated['officer_name']) && $validated['officer_name'] !== $lockedOfficer->officer_name) {
                $this->checkNameRateLimit($lockedOfficer->id);
                $check = NameSkeleton::checkName($validated['officer_name'], $user->id);
                if ($check['blocked']) {
                    throw ValidationException::withMessages([
                        'officer_name' => $check['reason'] ?? 'Nama karakter perwira tidak diizinkan atau menyerupai perwira lain.',
                    ]);
                }
                $updateData['officer_name'] = $validated['officer_name'];
                $nameChanged = true;
            }

            if (isset($validated['streamer_name']) && $validated['streamer_name'] !== $lockedOfficer->streamer_name) {
                $check = NameSkeleton::checkName($validated['streamer_name'], $user->id);
                if ($check['blocked']) {
                    throw ValidationException::withMessages([
                        'streamer_name' => $check['reason'] ?? 'Nama streamer tidak diizinkan atau menyerupai perwira lain.',
                    ]);
                }
                $updateData['streamer_name'] = $validated['streamer_name'];
            }

            // 2. Check Callsign & Badge unique & cooldown (max 1x per 7 days)
            $newCallsign = isset($validated['callsign']) ? trim($validated['callsign']) : null;
            $newBadge = isset($validated['badge_number']) ? trim($validated['badge_number']) : null;

            $callsignDiffers = $newCallsign !== null && strtoupper($newCallsign) !== strtoupper(trim($lockedOfficer->callsign ?? ''));
            $badgeDiffers = $newBadge !== null && strtoupper($newBadge) !== strtoupper(trim($lockedOfficer->badge_number ?? ''));

            if ($callsignDiffers || $badgeDiffers) {
                if (!$user->isAdmin()) {
                    if ($lockedOfficer->callsign_changed_at && $lockedOfficer->callsign_changed_at->diffInDays(now()) < 7) {
                        $remaining = 7 - $lockedOfficer->callsign_changed_at->diffInDays(now());
                        throw ValidationException::withMessages([
                            'callsign' => "Callsign dan nomor badge hanya dapat diubah maksimal 1 kali dalam 7 hari. Tunggu {$remaining} hari lagi.",
                        ]);
                    }
                }

                if ($callsignDiffers) {
                    $normCallsign = strtoupper($newCallsign);
                    $exists = Officer::where('is_active', true)
                        ->where('id', '!=', $lockedOfficer->id)
                        ->whereRaw('UPPER(TRIM(callsign)) = ?', [$normCallsign])
                        ->exists();

                    if ($exists) {
                        throw ValidationException::withMessages([
                            'callsign' => "Callsign '{$newCallsign}' sudah digunakan oleh perwira dinas aktif lain.",
                        ]);
                    }
                    $updateData['callsign'] = $newCallsign;
                    $callsignOrBadgeChanged = true;
                }

                if ($badgeDiffers) {
                    if (!empty($newBadge)) {
                        $normBadge = strtoupper($newBadge);
                        $exists = Officer::where('is_active', true)
                            ->where('id', '!=', $lockedOfficer->id)
                            ->whereNotNull('badge_number')
                            ->whereRaw('UPPER(TRIM(badge_number)) = ?', [$normBadge])
                            ->exists();

                        if ($exists) {
                            throw ValidationException::withMessages([
                                'badge_number' => "Nomor badge '{$newBadge}' sudah digunakan oleh perwira lain.",
                            ]);
                        }
                    }
                    $updateData['badge_number'] = $newBadge;
                    $callsignOrBadgeChanged = true;
                }

                if ($callsignOrBadgeChanged) {
                    $updateData['callsign_changed_at'] = now();
                }
            }

            // 3. Avatar
            if (isset($validated['avatar_url']) && $validated['avatar_url'] !== $lockedOfficer->avatar_url) {
                $updateData['avatar_url'] = $validated['avatar_url'];
                $updateData['avatar_is_custom'] = true;
            }

            // 4. Patrol zone & division
            if (array_key_exists('patrol_zone', $validated)) {
                $updateData['patrol_zone'] = $validated['patrol_zone'];
            }
            if (array_key_exists('division_id', $validated)) {
                $updateData['division_id'] = $validated['division_id'];
            }

            if (empty($updateData)) {
                return response()->json([
                    'message' => 'Tidak ada perubahan data.',
                    'officer' => $lockedOfficer->load(['bioProfile', 'agencyRelation', 'rankRelation', 'divisionRelation']),
                ]);
            }

            $updateData['owner_updated_at'] = now();
            $lockedOfficer->update($updateData);

            if ($lockedOfficer->bioProfile) {
                BioProfileService::invalidatePublicCache($lockedOfficer->bioProfile->slug);
            }

            AuditLogger::log(
                'officer.self_updated',
                'officer',
                $lockedOfficer->id,
                $beforeState,
                $updateData,
                'Perwira memperbarui profil dinas mandiri'
            );

            if ($nameChanged) {
                AuditLogger::log(
                    'officer.name_changed',
                    'officer',
                    $lockedOfficer->id,
                    ['officer_name' => $beforeState['officer_name']],
                    ['officer_name' => $updateData['officer_name']],
                    'Perwira mengubah nama karakter mandiri'
                );
            }

            if ($callsignOrBadgeChanged) {
                AuditLogger::log(
                    'officer.callsign_changed',
                    'officer',
                    $lockedOfficer->id,
                    ['callsign' => $beforeState['callsign'], 'badge_number' => $beforeState['badge_number']],
                    ['callsign' => $updateData['callsign'] ?? $lockedOfficer->callsign, 'badge_number' => $updateData['badge_number'] ?? $lockedOfficer->badge_number],
                    'Perwira mengubah callsign/badge mandiri'
                );
            }

            return response()->json([
                'message' => 'Profil dinas Anda berhasil diperbarui.',
                'officer' => $lockedOfficer->load(['bioProfile', 'agencyRelation', 'rankRelation', 'divisionRelation']),
            ]);
        });
    }

    /**
     * Check name change rate limit (max 3 times in 30 days, counting only name changes).
     */
    protected function checkNameRateLimit(int $officerId): void
    {
        $count = AuditLog::where('action', 'officer.name_changed')
            ->where('subject_type', 'officer')
            ->where('subject_id', $officerId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($count >= 3) {
            throw ValidationException::withMessages([
                'officer_name' => 'Batas pengubahan nama tercapai (maksimal 3 kali dalam 30 hari). Hubungi Admin jika butuh penyesuaian darurat.',
            ]);
        }
    }

    /**
     * Officer self audit history.
     */
    public function history(): JsonResponse
    {
        $officer = Auth::user()->officer;

        if (!$officer) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $logs = AuditLog::where(function ($q) use ($officer) {
            $q->where('subject_type', 'officer')->where('subject_id', $officer->id);
        })
        ->orWhere(function ($q) use ($officer) {
            if ($officer->bioProfile) {
                $q->where('subject_type', 'officer_bio_profile')->where('subject_id', $officer->bioProfile->id);
            }
        })
        ->latest()
        ->paginate(25);

        return response()->json([
            'logs' => $logs,
        ]);
    }
}
