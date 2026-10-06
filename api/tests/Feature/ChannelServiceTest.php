<?php

namespace Tests\Feature;

use App\Enums\ChannelJoinModes;
use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Enums\ProfileableTypes;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use App\Services\ChannelService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelServiceTest extends TestCase
{
    #[Test]
    public function channel_creation_ignores_explicit_data()
    {
        $user = User::factory()->create();
        $channelOwner = User::factory()->create();

        $creationTime = '2026-10-01 14:00:00';
        $injectingTime = '2026-10-30 15:00:00';
        Carbon::setTestNow($creationTime);
        Message::factory(10)->create();
        $channel = app(ChannelService::class)->createChannel([
            'name' => 'Test Channel',
            'description' => null,
            'visibility' => ChannelVisibility::Public,
            'type' => ChannelType::Channel,
            'join_mode' => ChannelJoinModes::Open,
            'handle' => 'test_channel',

            // trying to inject unwanted data
            'role' => ChannelRoles::Admin,
            'messages_count' => 10,
            'last_message_id' => 5,
            'last_activity_at' => $injectingTime,
            'owner_id' => $user->id,
        ], $channelOwner);

        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'messages_count' => 0,
            'last_message_id' => null,
            'last_activity_at' => Carbon::parse($creationTime),
            'owner_id' => $channelOwner->id,
        ]);
        $this->assertDatabaseHas(Profile::class, [
            'profileable_id' => $channel->id,
            'profileable_type' => ProfileableTypes::Channel,
            'handle' => 'test_channel',
        ]);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $channelOwner->profile->id,
            'role' => ChannelRoles::Owner,
            'status' => ChannelMemberStatus::Approved,
        ]);
        $this->assertDatabaseMissing(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
        ]);
    }
}
