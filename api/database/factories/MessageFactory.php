<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Enums\ProfileableTypes;
use App\Models\Conversation;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if ($this->state['sender_id'] ?? false) {
            $sender_id = $this->state['sender_id'];
        } else {
            $user = User::factory()->create();
            $sender = Profile::where('profileable_type', ProfileableTypes::User)
                ->where('profileable_id', $user->id)->first();
            $sender_id = $sender->id;
        }

        if ($this->state['receiver_id'] ?? false) {
            $receiver_id = $this->state['receiver_id'];
        } else {
            $user = User::factory()->create();
            $receiver = Profile::where('profileable_type', ProfileableTypes::User)
                ->where('profileable_id', $user->id)->first();
            $receiver_id = $receiver->id;
        }

        [$lowerId, $higherId] = Conversation::normalizeProfiles(
            $sender_id,
            $receiver_id
        );

        return [
            'sender_id' => $sender_id,
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,
            'body' => fake()->paragraph(),
            'type' => MessageType::Text,
            'is_read' => false,
            'messageable_type' => Conversation::class,
            'messageable_id' => Conversation::factory()->state([
                'lower_profile_id' => $lowerId,
                'higher_profile_id' => $higherId,
            ]),
        ];
    }
}
