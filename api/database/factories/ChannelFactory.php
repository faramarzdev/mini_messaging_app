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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->name(),
            'visibility' => ChannelVisibility::Public,
            'type' => ChannelType::Channel,
        ];
    }
}
