<?php

namespace Database\Factories;

use App\Enums\MessageableType;
use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id' => fn () => User::factory()->create()->profile->id,   // must stay above messageable_id
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,
            'body' => fake()->paragraph(),
            'type' => MessageType::Text,
            'is_read' => false,
            'messageable_type' => MessageableType::Conversation,
            // runs only when the caller did not pass messageable_id
            'messageable_id' => fn (array $attributes) => $this->conversationFor($attributes['sender_id']),
        ];
    }

    private function conversationFor(int $senderProfileId): int
    {
        $otherProfileId = User::factory()->create()->profile->id;
        [$lower, $higher] = Conversation::normalizeProfiles($senderProfileId, $otherProfileId);

        return Conversation::factory()->create([
            'lower_profile_id' => $lower,
            'higher_profile_id' => $higher,
        ])->id;
    }
}
