<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public static function getOrCreateBetween(Profile $profileA, Profile $profileB): Conversation
    {
        [$lowerId, $higherId] = Conversation::normalizeProfiles($profileA->id, $profileB->id);

        return Conversation::firstOrCreate([
            'lower_profile_id' => $lowerId,
            'higher_profile_id' => $higherId,
        ], [
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
    }

    public static function getBetween(Profile $profileA, Profile $profileB): ?Conversation
    {
        [$lowerId, $higherId] = Conversation::normalizeProfiles($profileA->id, $profileB->id);
        $conversation = Conversation::where('lower_profile_id', $lowerId)
            ->where('higher_profile_id', $higherId);
        if ($conversation->exists()) {
            return $conversation->first();
        }

        return null;

    }

    public function hide(Conversation $conversation, Profile $profile)
    {
        return DB::transaction(function () use ($conversation, $profile) {
            // hide all the messages for this conversation and this profile
            MessageService::hideAllMessagesForProfileConversation($conversation, $profile);

            // hide the conversation for this profile
            if ($conversation->lower_profile_id == $profile->id) {
                $conversation->update(['is_available_for_lower_profile' => false]);
            } else {
                $conversation->update(['is_available_for_higher_profile' => false]);
            }

            // (soft) delete the conversation if it's hidden for both profiles
            if (! $conversation->is_available_for_lower_profile && ! $conversation->is_available_for_higher_profile) {
                $conversation->delete();
            }

            return $conversation;
        });

    }
}
