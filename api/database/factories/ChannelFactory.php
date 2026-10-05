<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    public function definition(): array
    {
        if (isset($this->state['last_activity_at'])) {
            $lastActivityAt = Carbon::parse($this->state['last_activity_at']);
        } else {
            $lastActivityAt = Carbon::now();
        }

        return [
            'owner_id' => User::factory(),
            'name' => fake()->name(),
            'visibility' => ChannelVisibility::Public,
            'type' => ChannelType::Channel,
            'last_activity_at' => $lastActivityAt,
        ];
    }
}
