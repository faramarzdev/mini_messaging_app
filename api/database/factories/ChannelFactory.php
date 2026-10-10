<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => fn () => User::factory(),
            'name' => fake()->name(),
            'visibility' => ChannelVisibility::Public,
            'type' => ChannelType::Channel,
            'last_activity_at' => now(),
        ];
    }
}
