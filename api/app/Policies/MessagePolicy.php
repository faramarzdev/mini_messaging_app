<?php

namespace App\Policies;

use App\Enums\ChannelRoles;
use App\Enums\ChannelVisibility;
use App\Enums\MessageableType;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;

class MessagePolicy
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
    public function view(User $user, Message $message): bool
    {
        $profileId = $user->profile?->id;
        if (! $profileId) {
            return false;
        }

        if ($message->messageable_type === MessageableType::Conversation->value) {
            if (
                ($message->sender_id === $profileId && $message->is_available_on_sender) ||
                ($message->is_available_on_receiver && in_array($profileId, $message->messageable->connectedProfilesIds))
            ) {
                return true;
            }

        } elseif ($message->messageable_type === MessageableType::Channel->value) {
            $channel = Channel::where('id', $message->messageable_id)->first();
            if ($channel) {
                if ($channel->visibility == ChannelVisibility::Public->value) {
                    return true;
                } elseif (in_array($profileId, $channel->members->pluck('id')->toArray())) {
                    return true;
                }
            }
        }

        return false;
    }

    public function viewMessages(User $user, Profile $profile): bool
    {
        $viewerProfileId = $user->profile?->id;
        if (! $viewerProfileId) {
            return false;
        }

        if ($profile->profileable_type === ProfileableTypes::User->value) {
            return true; // user can
        } elseif ($profile->profileable_type === ProfileableTypes::Channel->value) {
            if ($profile->profileable->visibility == ChannelVisibility::Public->value) {
                return true;
            } elseif ($profile->profileable->profileRole()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if (! $user->isLimited()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Message $message): bool
    {
        $profileId = $user->profile?->id;
        if (! $profileId) {
            return false;
        }

        if (
            $message->sender_id === $profileId &&
            $message->is_available_on_sender &&
            $message->created_at->greaterThan(now()->subHours(2))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can HIDE the model .
     */
    public function hide(User $user, Message $message): bool
    {
        $profileId = $user->profile?->id;
        if (! $profileId) {
            return false;
        }

        if ($message->messageable_type === MessageableType::Conversation->value) {
            if (in_array($profileId, $message->messageable->connectedProfilesIds())) {
                return true;
            }
        } elseif ($message->messageable_type === MessageableType::Channel->value) {
            $profileInChannel = ChannelMember::where('channel_id', $message->messageable_id)->where('profile_id', $profileId)->first();
            if ($profileInChannel) {
                $userRole = $profileInChannel->role;
                if (in_array($userRole, [ChannelRoles::Owner->value, ChannelRoles::Admin->value])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Message $message): bool
    {
        $profileId = $user->profile?->id;
        if (! $profileId) {
            return false;
        }

        if (
            $profileId === $message->sender_id &&
            $message->is_available_on_sender &&
            ! $message->is_read
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Message $message): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Message $message): bool
    {
        return false;
    }
}
