<?php

namespace Tests\Concerns;

use App\Enums\ChannelType;
use App\Models\User;
use App\Services\ChannelService;

trait CreatesChannels
{
    private ChannelService $channelService;

    protected function createChannel(?User $owner = null, string $channelType = ChannelType::Channel->value): array
    {
        if (! $owner) {
            $owner = User::factory()->create();
        }
        $channel = app(ChannelService::class)->createChannel([
            'name' => 'Test Channel',
            'visibility' => 'public',
            'type' => $channelType,
        ], $owner);

        return [$channel, $owner];
    }
}
