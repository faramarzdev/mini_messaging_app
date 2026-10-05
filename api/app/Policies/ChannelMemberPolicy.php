<?php

namespace App\Policies;

use App\Enums\ChannelRoles;
use App\Models\Channel;
use App\Models\User;

class ChannelMemberPolicy
{
    /**
     * Create a new policy instance.
     */
    public function view(User $user, Channel $channel): bool
    {
        $role = $channel->profileRole();
        if (in_array($role, [ChannelRoles::Owner, ChannelRoles::Admin])) {
            return true;
        }

        return false;
    }
}
