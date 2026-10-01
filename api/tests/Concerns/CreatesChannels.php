<?php

namespace Tests\Concerns;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Models\ChannelMember;
use App\Models\User;
use App\Services\ChannelService;

trait CreatesChannels
{
    private ChannelService $channelService;

    /***
     * @param  User|null  $owner
     * @param  ChannelType  $channelType
     * @param  User|null  $member
     * @param  ChannelVisibility  $channelVisibility
     * @return array: Channel, User owner, ?User Member
     */
    protected function createChannel(
        ?User $owner = null,
        ChannelType $channelType = ChannelType::Channel,
        ?User $member = null,
        ChannelVisibility $channelVisibility = ChannelVisibility::Public,
    ): array {
        if (! $owner) {
            $owner = User::factory()->create();
        }
        $channel = app(ChannelService::class)->createChannel([
            'name' => 'Test Channel',
            'visibility' => $channelVisibility,
            'type' => $channelType,
        ], $owner);

        if ($member) {
            ChannelMember::factory()->create([
                'channel_id' => $channel->id,
                'profile_id' => $member->profile->id,
                'status' => ChannelMemberStatus::Approved->value,
                'role' => ChannelRoles::Member->value,
                'joined_at' => now(),
            ]);
        }

        return [$channel, $owner, $member];
    }
}
