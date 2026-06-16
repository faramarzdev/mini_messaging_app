<?php

namespace Database\Factories;

use App\Enums\ProfileableTypes;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if ($this->state['lower_profile_id'] ?? false) {
            $lowerProfileId = $this->state['lower_profile_id'];
        } else {
            $user = User::factory()->create();
            // user creation makes the profile
            $profile = Profile::where('profileable_type', ProfileableTypes::User->value)
                ->where('profileable_id', $user->id)->first();
            $lowerProfileId = $profile->id;
        }

        if ($this->state['higher_profile_id'] ?? false) {
            $higherProfileId = $this->state['higher_profile_id'];
        } else {
            $higherUser = User::factory()->create();
            $higherProfile = Profile::where('profileable_type', ProfileableTypes::User->value)
                ->where('profileable_id', $higherUser->id)->first();
            $higherProfileId = $higherProfile->id;
        }

        return [
            'lower_profile_id' => $lowerProfileId,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $higherProfileId,
            'is_available_for_higher_profile' => true,
            'last_message_id' => null,
        ];
    }
}
