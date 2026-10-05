<?php

namespace App\Policies;

use App\Enums\ChannelVisibility;
use App\Models\Channel;
use App\Models\User;

class ChannelPolicy
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
    public function view(?User $user, Channel $channel): bool
    {

        if ($channel->visibility === ChannelVisibility::Public) {
            return true;
        }
        if ($user) {
            if ($channel->owner_id === $user->id) {
                return true;
            }
            // todo: check if the user is the private channel's member
            $profile = $user->profile;
            if ($channel->members->contains($profile)) {
                return true;
            }
        }

        return false;

        // todo: think about
        //      " channels' profiles are kinda public , only private channel messages must be protected (visible to members) "
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $userChannels = Channel::where('owner_id', $user->id)->count();
        if ($userChannels < config('app.max_channels_per_user')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Channel $channel): bool
    {
        if ($channel->owner_id === $user->id) {
            return true;
        }

        // todo: implement channel managers
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Channel $channel): bool
    {
        if ($channel->owner_id === $user->id) {
            return true;
        }

        // todo: implement channel managers
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Channel $channel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Channel $channel): bool
    {
        return false;
    }
}
