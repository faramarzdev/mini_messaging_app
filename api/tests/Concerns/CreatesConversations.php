<?php

namespace Tests\Concerns;

use App\Enums\MessageableType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

trait CreatesConversations
{
    /***
     * @param  User|null  $lowerUser
     * @param  User|null  $higherUser
     * @param  bool  $makeMessage message will be created using lowerUser
     * @return array
     */
    protected function createConversation(?User $lowerUser = null, ?User $higherUser = null, bool $makeMessage = false): array
    {
        if (! $lowerUser) {
            $lowerUser = User::factory()->create();
        }
        if (! $higherUser) {
            $higherUser = User::factory()->create();
        }

        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $lowerUser->profile->id,
            'higher_profile_id' => $higherUser->profile->id,
        ]);

        $message = null;
        if ($makeMessage) {
            $message = Message::factory()->create([
                'sender_id' => $lowerUser->profile->id,
                'messageable_type' => MessageableType::Conversation->value,
                'messageable_id' => $conversation->id,
            ]);
            $conversation->update([
                'last_message_id' => $message->id,
                'last_activity_at' => Carbon::now(),
            ]);
            $conversation->fresh();
        }

        return [$conversation, $lowerUser, $higherUser, $message];
    }
}
