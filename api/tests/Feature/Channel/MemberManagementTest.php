<?php

namespace Tests\Feature\Channel;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

class MemberManagementTest extends ChannelTestCase
{
    #[Test]
    public function management_can_see_all_members(): void
    {
        [$channel, $owner] = $this->createChannel();

        ChannelMember::factory(20)->state(['channel_id' => $channel->id])->create();

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson(route('channel_member.index', ['channel' => $channel->id]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(21, 'data'); // 20 + 1 (the owner)

    }

    #[Test]
    public function management_can_see_pending_join_requests(): void
    {
        [$channel, $owner] = $this->createChannel();

        ChannelMember::factory(10)->state(['channel_id' => $channel->id, 'status' => ChannelMemberStatus::Pending])->create();
        ChannelMember::factory(10)->state(['channel_id' => $channel->id, 'status' => ChannelMemberStatus::Approved])->create();

        $route = route('channel_member.index', ['channel' => $channel->id, 'status' => ChannelMemberStatus::Pending]);
        $response = $this->actingAs($owner, 'sanctum')->getJson($route);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function channel_managements_can_block_members(): void
    {
        $member = User::factory()->create();
        [$channel] = $this->createChannel(member: $member);

        $admin = User::factory()->create();
        $channelAdmin = [
            'channel_id' => $channel->id,
            'profile_id' => $admin->profile->id,
            'role' => ChannelRoles::Admin,
            'status' => ChannelMemberStatus::Approved,
        ];
        ChannelMember::factory()->state($channelAdmin)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                route('channel_member.block', ['channel' => $channel->id]),
                [
                    'profile_id' => $member->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseCount(ChannelMember::class, 3);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Blocked,
        ]);
    }

    #[Test]
    public function users_without_permission_cannot_block_members(): void
    {
        $member = User::factory()->create();
        [$channel] = $this->createChannel(member: $member);

        $memberB = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $memberB->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Approved,
        ])->create();
        $this->assertDatabaseCount(ChannelMember::class, 3);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson(
                route('channel_member.block', ['channel' => $channel->id]),
                [
                    'profile_id' => $memberB->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseCount(ChannelMember::class, 3);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $memberB->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Approved,
        ]);
    }
}
