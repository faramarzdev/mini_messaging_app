<?php

namespace Database\Factories;

use App\Enums\ProfileableTypes;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfilePicture>
 */
class ProfilePictureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        if ($this->state['profile_id'] ?? false) {
            $profileId = $this->state['profile_id'];
        } else {
            $user = User::factory()->create();
            $profile = Profile::where('profileable_type', ProfileableTypes::User->value)
                ->where('profileable_id', $user->id)->first();
            $profileId = $profile->id;
        }

        return [
            'uuid' => Str::uuid(),
            'profile_id' => $profileId,
            'path' => $this->faker->imageUrl(),
            'original_name' => $this->faker->name(),
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->randomNumber(),
        ];
    }
}
