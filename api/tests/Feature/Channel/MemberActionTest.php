<?php

namespace Tests\Feature\Channel;

use App\Enums\ChannelJoinModes;
use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class MemberActionTest extends ChannelTestCase
{
    #[Test]
    public function already_joined_member_cannot_join_again()
    {
        [$channel] = $this->createChannel(member: $this->user);
        $this->assertDatabaseCount(ChannelMember::class, 2); // owner + this member
        $response = $this->postJson(
            route('channel_member.join', ['channel' => $channel->id])
        );
        $response->assertStatus(Response::HTTP_CONFLICT);
        $this->assertDatabaseCount(ChannelMember::class, 2); // owner + this member
    }

    #[Test]
    public function blocked_users_cannot_join()
    {
        [$channel] = $this->createChannel();
        $user = User::factory()->create();
        $blockedMember = [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Blocked,
        ];
        ChannelMember::factory()->state($blockedMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 2);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $blockedMember);
    }

    #[Test]
    public function users_can_join_channel()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create([
            'join_mode' => ChannelJoinModes::Open,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseCount(ChannelMember::class, 1);
    }

    #[Test]
    public function users_can_not_join_closed_channel()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create([
            'join_mode' => ChannelJoinModes::Closed,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(ChannelMember::class, 0);
    }

    #[Test]
    public function users_can_request_to_join_channel_which_needed_approval()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create([
            'join_mode' => ChannelJoinModes::ApprovalNeeded,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Pending,
        ]);
    }

    #[Test]
    public function user_can_leave_channel_they_joined()
    {

        [$channel] = $this->createChannel(member: $this->user);
        $this->assertDatabaseCount(ChannelMember::class, 2); // owner + this member

        $response = $this->deleteJson(
            route('channel_member.leave', ['channel' => $channel->id])
        );
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $this->user->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Left,
        ]);
    }

    #[Test]
    public function left_member_can_join_again_public_without_approval(): void
    {
        [$channel] = $this->createChannel();
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $this->user->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Left,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

        $response = $this->postJson(
            route('channel_member.join', ['channel' => $channel->id])
        );
        $response->assertStatus(Response::HTTP_OK);

        $channelMember['status'] = ChannelMemberStatus::Approved;
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);
    }

    #[Test]
    public function no_more_than_one_join_request_can_be_sent(): void
    {
        [$channel] = $this->createChannel(channelVisibility: ChannelVisibility::Private);
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $this->user->profile->id,
            'role' => ChannelRoles::Member,
            'status' => ChannelMemberStatus::Pending,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

        $response = $this->postJson(
            route('channel_member.join', ['channel' => $channel->id])
        );
        $response->assertStatus(Response::HTTP_CONFLICT);

        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

    }

    // Todo: write these when implementing the invite
    // invited_user_join_request_would_be_handled_correcly
    // invited_user_can_accept_and_join_the_channel
    // invited_user_can_reject_the_channel_invitation
    // invited_user_can_reject_and_report_the_channel_invitation

    #[Test]
    #[DataProvider('accessMatrix')]
    public function reading_access_matches_the_status_matrix(ChannelVisibility $visibility, ?ChannelMemberStatus $status, bool $canSee, bool $canSend): void
    {
        // Arrange: a group with $visibility; if $status !== null, a ChannelMember row with that status for $viewer
        [$group] = $this->createChannel(channelType: ChannelType::Group, channelVisibility: $visibility);
        if ($status) {
            ChannelMember::factory()->state([
                'channel_id' => $group->id,
                'profile_id' => $this->user->profile->id,
                'role' => ChannelRoles::Member,
                'status' => $status,
            ])->create();

            $this->assertDatabaseHas(ChannelMember::class, [
                'channel_id' => $group->id,
                'profile_id' => $this->user->profile->id,
                'status' => $status,
            ]);
        }

        $seeingResponse = $this->getJson(route('profile.messages.index', ['profile' => $group->profile->handle]));
        if ($canSee) {
            $seeingResponse->assertStatus(Response::HTTP_OK);
        } else {
            $seeingResponse->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    #[Test]
    #[DataProvider('accessMatrix')]
    public function sending_access_matches_the_status_matrix(ChannelVisibility $visibility, ?ChannelMemberStatus $status, bool $canSee, bool $canSend): void
    {
        // Arrange: a group with $visibility; if $status !== null, a ChannelMember row with that status for $viewer
        [$group] = $this->createChannel(channelType: ChannelType::Group, channelVisibility: $visibility);
        if ($status) {
            ChannelMember::factory()->state([
                'channel_id' => $group->id,
                'profile_id' => $this->user->profile->id,
                'role' => ChannelRoles::Member,
                'status' => $status,
            ])->create();

            $this->assertDatabaseHas(ChannelMember::class, [
                'channel_id' => $group->id,
                'profile_id' => $this->user->profile->id,
                'status' => $status,
            ]);
        }

        Event::fake([MessageSent::class]);
        $sendingResponse = $this->postJson(route('message.store'), [
            'receiver_handle' => $group->profile->handle,
            'body' => 'Just a test',
        ]);
        if ($canSend) {
            $sendingResponse->assertStatus(Response::HTTP_CREATED);
        } else {
            $sendingResponse->assertStatus(Response::HTTP_FORBIDDEN);
        }
    }

    /***
     * @return array[] each item as: description/index => ["channel visibility setting", "user membership status", "can see", "can send"]
     * ONLY for GROUP
     * MEMBER cannot send on CHANNEL
     */
    public static function accessMatrix(): array
    {
        return [
            // description => channel visibility setting, user membership status, can see, can send
            'public, no row' => [ChannelVisibility::Public, null, true, true],
            'public, approved' => [ChannelVisibility::Public, ChannelMemberStatus::Approved, true, true],
            'public, invited' => [ChannelVisibility::Public, ChannelMemberStatus::Invited,  true, true],
            'public, pending' => [ChannelVisibility::Public, ChannelMemberStatus::Pending,  true, true],
            'public, left' => [ChannelVisibility::Public, ChannelMemberStatus::Left,     true, true],
            'public, blocked' => [ChannelVisibility::Public, ChannelMemberStatus::Blocked,  false, false],

            'private, no row' => [ChannelVisibility::Private, null, false, false],
            'private, approved' => [ChannelVisibility::Private, ChannelMemberStatus::Approved, true, true],
            'private, invited' => [ChannelVisibility::Private, ChannelMemberStatus::Invited,  true, true],
            'private, pending' => [ChannelVisibility::Private, ChannelMemberStatus::Pending,  false, false],
            'private, left' => [ChannelVisibility::Private, ChannelMemberStatus::Left,     false, false],
            'private, blocked' => [ChannelVisibility::Private, ChannelMemberStatus::Blocked,  false, false],
        ];
    }
}
