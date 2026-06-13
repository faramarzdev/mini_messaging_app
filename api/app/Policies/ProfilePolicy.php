<?php

namespace App\Policies;

use App\Enums\ChannelRoles;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Profile $profile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Profile $profile): bool
    {
        if ($profile->profileable_type === ProfileableTypes::User->value) {
            if ($profile->profileable_id === $user->id) {
                return true;
            }
        } elseif ($profile->profileable_type === ProfileableTypes::Channel->value) {
            $channel = Channel::findOrFail($profile->profileable_id);
            if ($channel->owner_id === $user->id) {
                return true;
            }
            $userProfile = $user->profile;
            $profileInChannel = ChannelMember::where('channel_id', $channel->id)->where('profile_id', $userProfile->id)->first();
            if ($profileInChannel) {
                $userRole = $profileInChannel->role;
                if (in_array($userRole, [ChannelRoles::Owner, ChannelRoles::Admin])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Profile $profile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Profile $profile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Profile $profile): bool
    {
        return false;
    }
}
