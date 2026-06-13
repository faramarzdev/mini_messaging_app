<?php

namespace Database\Factories;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChannelMember>
 */
class ChannelMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'profile_id' => User::factory()->create()->profile->id,
            'last_read_message_id' => null,
            'role' => ChannelRoles::Member->value,
            'status' => fake()->randomElement(ChannelMemberStatus::cases()),
        ];
    }
}
