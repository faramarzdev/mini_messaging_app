<?php

namespace Tests\Feature\Channel;

use App\Enums\ChannelJoinModes;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

abstract class ChannelTestCase extends TestCase
{
    use CreatesChannels, CreatesConversations, RefreshDatabase;

    protected array $channelSample = [
        'name' => 'Test Channel',
        'description' => null,
        'visibility' => ChannelVisibility::Public,
        'type' => ChannelType::Channel,
        'join_mode' => ChannelJoinModes::Open,
    ];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }
}
