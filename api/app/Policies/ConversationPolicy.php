<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
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
    public function view(User $user, Conversation $conversation): bool
    {
        $profileId = $user->profile->id;
        if (
            ($conversation->lower_profile_id === $profileId && $conversation->is_available_for_lower_profile) ||
            ($conversation->higher_profile_id === $profileId && $conversation->is_available_for_higher_profile)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can hide the model.
     */
    public function hide(User $user, Conversation $conversation): bool
    {
        $profileId = $user->profile->id;
        $connectedProfilesIds = $conversation->connectedProfilesIds();
        if ($connectedProfilesIds && in_array($profileId, $connectedProfilesIds)) {
            return true;
        }

        return false;
    }

    /**
     * these actions will be done automatically by the system.
     *
     * only the system must create a conversation (when a message send for the first time between two profile)
     * public function create(User $user): bool
     *
     * no conversation will be updated, on user delete the system must update (set null) for that id
     * public function update(User $user, Conversation $conversation): bool
     *
     * conversations will be removed when both participants (profiles) are set null and there are no message belongs to it.
     * public function delete(User $user, Conversation $conversation): bool
     *
     *
     * public function restore(User $user, Conversation $conversation): bool
     * public function forceDelete(User $user, Conversation $conversation): bool
     */
}
