<?php

namespace App\Http\Controllers;

use App\Models\ClipperDelegation;
use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\BioSlugRedirect;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BioProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficerBindingController extends Controller
{
    /**
     * Bind a verified user account to an officer profile.
     */
    public function bindUser(Request $request, Officer $officer): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'user_id' => 'User dengan status banned tidak dapat di-bind ke profil perwira.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'user_id' => 'User wajib memiliki alamat email yang telah diverifikasi sebelum dihubungkan ke perwira.',
            ]);
        }

        // Check if user is already bound to another officer
        $alreadyBound = Officer::where('user_id', $user->id)
            ->where('id', '!=', $officer->id)
            ->first();

        if ($alreadyBound) {
            throw ValidationException::withMessages([
                'user_id' => "User ini sudah terikat ke perwira lain: {$alreadyBound->officer_name} ({$alreadyBound->callsign}).",
            ]);
        }

        return DB::transaction(function () use ($officer, $user, $request) {
            // Lock officer row to prevent concurrent race condition
            $lockedOfficer = Officer::where('id', $officer->id)->lockForUpdate()->firstOrFail();

            // Check if officer is already bound to another user (B-02)
            if ($lockedOfficer->user_id !== null && $lockedOfficer->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'officer' => 'Perwira ini sudah terikat ke akun lain. Lakukan unbind terlebih dahulu sebelum mengikatkan akun baru.',
                ]);
            }

            $beforeState = [
                'user_id' => $lockedOfficer->user_id,
            ];

            $lockedOfficer->user_id = $user->id;
            $lockedOfficer->save();

            // Ensure OfficerBioProfile exists
            $profile = BioProfileService::ensureBioProfile($lockedOfficer);
            $profile->is_active = true;
            $profile->save();

            BioProfileService::invalidatePublicCache($profile->slug);

            AuditLogger::log(
                'officer.bind',
                'officer',
                $lockedOfficer->id,
                $beforeState,
                ['user_id' => $user->id, 'profile_slug' => $profile->slug],
                $request->reason ?? "Bound to user {$user->name} ({$user->email})"
            );

            return response()->json([
                'message' => "Berhasil menghubungkan {$lockedOfficer->officer_name} ke user {$user->name}.",
                'officer' => $lockedOfficer->load(['user', 'bioProfile']),
            ]);
        });
    }

    /**
     * Revoke / Unbind user from officer profile (dishonorable discharge / resignation).
     */
    public function unbindUser(Request $request, Officer $officer): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        if (!$officer->user_id) {
            return response()->json([
                'message' => 'Perwira ini saat ini tidak terikat ke akun user mana pun (Unclaimed).',
            ], 422);
        }

        return DB::transaction(function () use ($officer, $request) {
            $lockedOfficer = Officer::where('id', $officer->id)->lockForUpdate()->firstOrFail();
            $oldUserId = $lockedOfficer->user_id;
            $oldUser = User::find($oldUserId);

            $beforeState = [
                'user_id' => $oldUserId,
            ];

            $lockedOfficer->user_id = null;
            $lockedOfficer->save();

            // Hide bio profile (do not delete links)
            if ($lockedOfficer->bioProfile) {
                $lockedOfficer->bioProfile->update(['is_active' => false]);
                BioProfileService::invalidatePublicCache($lockedOfficer->bioProfile->slug);
            }

            // Revoke all delegations granted by this user
            $revokedCount = ClipperDelegation::where('granted_by_user_id', $oldUserId)->delete();

            // Transition legacy role if still 'officer'
            if ($oldUser && $oldUser->role === 'officer') {
                $oldUser->role = 'member';
                $oldUser->save();
            }

            AuditLogger::log(
                'officer.unbind',
                'officer',
                $lockedOfficer->id,
                $beforeState,
                ['user_id' => null, 'revoked_delegations' => $revokedCount],
                $request->reason
            );

            return response()->json([
                'message' => "Ikatan akun pada {$lockedOfficer->officer_name} berhasil dicabut. {$revokedCount} slot delegasi clipper telah dibatalkan.",
                'officer' => $lockedOfficer->load(['bioProfile']),
            ]);
        });
    }

    /**
     * Admin modifies bio-link profile slug, creating an automatic 301 redirect entry.
     */
    public function updateSlug(Request $request, OfficerBioProfile $profile): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string|min:3|max:40',
            'reason' => 'required|string|min:3|max:255',
        ]);

        $newSlug = strtolower(trim($request->slug));
        $oldSlug = $profile->slug;

        if ($newSlug === $oldSlug) {
            return response()->json(['message' => 'Slug tidak mengalami perubahan.'], 200);
        }

        $error = BioProfileService::validateSlug($newSlug, $profile->id);
        if ($error) {
            throw ValidationException::withMessages(['slug' => $error]);
        }

        return DB::transaction(function () use ($profile, $oldSlug, $newSlug, $request) {
            // Save old slug as 301 redirect
            BioSlugRedirect::updateOrCreate(
                ['old_slug' => $oldSlug],
                [
                    'officer_bio_profile_id' => $profile->id,
                    'created_at' => now(),
                ]
            );

            $profile->slug = $newSlug;
            $profile->save();

            BioProfileService::invalidatePublicCache($oldSlug);
            BioProfileService::invalidatePublicCache($newSlug);

            AuditLogger::log(
                'bio_profile.slug_changed',
                'officer_bio_profile',
                $profile->id,
                ['slug' => $oldSlug],
                ['slug' => $newSlug],
                $request->reason
            );

            return response()->json([
                'message' => "Slug berhasil diubah menjadi '{$newSlug}'. Pengunjung URL lama akan diarahkan otomatis via 301 Redirect.",
                'profile' => $profile,
            ]);
        });
    }
}
