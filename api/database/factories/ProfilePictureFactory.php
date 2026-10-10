<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfilePicture>
 */
class ProfilePictureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'profile_id' => fn () => User::factory()->create()->profile->id,
            'path' => $this->faker->imageUrl(),
            'original_name' => $this->faker->name(),
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->randomNumber(),
        ];
    }
}
