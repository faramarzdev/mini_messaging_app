<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {

        return [
            'lower_profile_id' => fn () => User::factory()->create()->profile->id,
            'higher_profile_id' => fn () => User::factory()->create()->profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
            'last_message_id' => null,
            'last_activity_at' => now(),
        ];
    }
}
