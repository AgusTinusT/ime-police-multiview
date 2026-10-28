<?php

namespace App\Policies;

use App\Models\OfficerBioLink;
use App\Models\OfficerBioProfile;
use App\Models\User;

class OfficerBioLinkPolicy
{
    /**
     * Determine whether the user can create links on the given bio profile.
     */
    public function create(User $user, OfficerBioProfile $profile): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $profile->officer && $profile->officer->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the bio link.
     */
    public function update(User $user, OfficerBioLink $link): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $link->profile && $link->profile->officer && $link->profile->officer->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the bio link.
     */
    public function delete(User $user, OfficerBioLink $link): bool
    {
        return $this->update($user, $link);
    }

    /**
     * Determine whether the user can toggle status of the bio link.
     */
    public function toggle(User $user, OfficerBioLink $link): bool
    {
        return $this->update($user, $link);
    }
}
