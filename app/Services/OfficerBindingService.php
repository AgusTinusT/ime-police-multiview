<?php

namespace App\Services;

use App\Models\Officer;
use App\Models\OfficerBioProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficerBindingService
{
    /**
     * Bind a verified user to an officer profile safely inside a transaction.
     *
     * @return array{0: Officer, 1: OfficerBioProfile}
     * @throws ValidationException
     */
    public static function bind(Officer $officer, User $user, ?string $reason = null, ?int $actorId = null): array
    {
        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'user' => 'User dengan status banned tidak dapat di-bind ke profil perwira.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'user' => 'User wajib memiliki alamat email yang telah diverifikasi sebelum dihubungkan ke perwira.',
            ]);
        }

        // Check if user is already bound to another officer
        $alreadyBound = Officer::where('user_id', $user->id)
            ->where('id', '!=', $officer->id)
            ->first();

        if ($alreadyBound) {
            throw ValidationException::withMessages([
                'user' => "User ini sudah terikat ke perwira lain: {$alreadyBound->officer_name} ({$alreadyBound->callsign}).",
            ]);
        }

        return DB::transaction(function () use ($officer, $user, $reason, $actorId) {
            // Lock officer row to prevent concurrent race condition
            $lockedOfficer = Officer::where('id', $officer->id)->lockForUpdate()->firstOrFail();

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

            // Ensure OfficerBioProfile exists and is active
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
                $reason ?? "Bound to user {$user->name} ({$user->email})",
                $actorId
            );

            return [$lockedOfficer, $profile];
        });
    }
}
