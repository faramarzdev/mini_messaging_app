<?php

namespace App\Policies;

use App\Enums\ChannelRoles;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Profile;
use App\Models\ProfilePicture;
use App\Models\User;

class ProfilePicturePolicy
{
    public function create(User $user, Profile $profile): bool
    {
        return $this->canManageProfile($user, $profile);
    }

    public function delete(User $user, ProfilePicture $profilePicture): bool
    {
        $profile = $profilePicture->profile;

        return $this->canManageProfile($user, $profile);
    }

    private function canManageProfile(User $user, Profile $profile): bool
    {
        if ($profile->profileable_type === ProfileableTypes::User->value) {
            return $profile->profileable_id === $user->id;
        }

        if ($profile->profileable_type === ProfileableTypes::Channel->value) {
            $channel = Channel::findOrFail($profile->profileable_id);
            if ($channel->owner_id === $user->id) {
                return true;
            }
            $membership = ChannelMember::where('channel_id', $channel->id)
                ->where('profile_id', $user->profile->id)
                ->first();

            return $membership && in_array($membership->role, [ChannelRoles::Owner->value, ChannelRoles::Admin->value]);
        }

        return false;
    }
}
