<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    use RefreshDatabase;

    private array $channelToCreate = [
        'name' => 'Test Channel',
        'description' => null,
        'visibility' => ChannelVisibility::Public->value,
        'type' => ChannelType::Channel->value,
        'can_join_by_link' => 1,
        'confirm_joined' => 0,
    ];

    #[Test]
    public function user_can_create_channel()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('channel.store'), [
                ...$this->channelToCreate,
                'handle' => 'test_channel',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $channel = $response->json();

        $this->assertDatabaseHas(Channel::class, $this->channelToCreate);
        $this->assertDatabaseCount(Channel::class, 1);

        $this->assertDatabaseHas(Profile::class, [
            'profileable_id' => $channel['id'],
            'profileable_type' => ProfileableTypes::Channel->value,

            'handle' => 'test_channel',

        ]);
        $this->assertDatabaseCount(Profile::class, 2);
    }

    #[Test]
    public function management_can_see_all_members()
    {
        $admin = User::factory()->create();
        $channel = Channel::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $admin->profile->id,
            'role' => ChannelRoles::Admin->value,
            'status' => ChannelMemberStatus::Approved->value,

        ])->create();

        ChannelMember::factory(20)->state(['channel_id' => $channel->id])->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson(route('channel_member.index', ['channel' => $channel->id]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(21, 'data'); // 20 + 1 (the owner)

    }

    #[Test]
    public function management_can_see_pending_join_requests()
    {
        $admin = User::factory()->create();
        $channel = Channel::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $admin->profile->id,
            'role' => ChannelRoles::Admin->value,
            'status' => ChannelMemberStatus::Approved->value,

        ])->create();

        ChannelMember::factory(10)->state(['channel_id' => $channel->id, 'status' => ChannelMemberStatus::Pending->value])->create();
        ChannelMember::factory(10)->state(['channel_id' => $channel->id, 'status' => ChannelMemberStatus::Approved->value])->create();

        $route = route('channel_member.index', ['channel' => $channel->id, 'status' => ChannelMemberStatus::Pending->value]);
        $response = $this->actingAs($admin, 'sanctum')->getJson($route);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(10, 'data');
    }

    #[Test]
    public function channel_management_can_remove_the_channel()
    {
        $channelOwner = User::factory()->create();
        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('channel.store'), [
                ...$this->channelToCreate,
                'handle' => 'test_channel',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $channel = $response->json();
        $this->assertDatabaseCount(Channel::class, 1);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel['id']]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted(Channel::class, [
            'id' => $channel['id'],
        ]);
    }

    #[Test]
    public function channel_users_cannot_remove_the_channel()
    {
        $channelOwner = User::factory()->create();
        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('channel.store'), [
                ...$this->channelToCreate,
                'handle' => 'test_channel',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $channel = $response->json();
        $this->assertDatabaseCount(Channel::class, 1);

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel['id']])
            );
        $response->assertStatus(Response::HTTP_OK);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel['id']]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(Channel::class, 1);
    }

    #[Test]
    public function channel_removal_removes_the_messages_and_the_profile()
    {
        $channelOwner = User::factory()->create();
        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('channel.store'), [
                ...$this->channelToCreate,
                'handle' => 'test_channel',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseCount(Channel::class, 1);

        $channel = Channel::findOrFail($response->json('id'));
        $channelProfile = $channel->profile;

        $this->assertDatabaseCount(Message::class, 0);
        $messageOne = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_id' => $channelProfile->id,
                'body' => 'this is a test message',
            ])->assertStatus(Response::HTTP_CREATED);
        $messageTwo = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_id' => $channelProfile->id,
                'body' => 'this is a test message',
            ])->assertStatus(Response::HTTP_CREATED);
        $this->assertNotSoftDeleted(Message::class, [
            'id' => $messageOne->json('id'),
        ]);
        $this->assertNotSoftDeleted(Message::class, [
            'id' => $messageTwo->json('id'),
        ]);
        $this->assertDatabaseCount(Message::class, 2);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel->id]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted(Message::class, [
            'id' => $messageOne->json('id'),
        ]);
        $this->assertSoftDeleted(Message::class, [
            'id' => $messageTwo->json('id'),
        ]);

        $this->assertSoftDeleted(Channel::class, [
            'id' => $channel->id,
        ]);
    }

    // user_can_see_public_channel
    // user_can_see_private_channel_when_joined
    // user_cant_see_private_channel
    // user_can_search_for_channel
    //

    #[Test]
    public function users_can_join_channel()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create([
            'can_join_by_link' => true,
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
            'can_join_by_link' => false,
        ]);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(ChannelMember::class, 0);
    }

    #[Test]
    public function users_can_request_to_join_channel()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create([
            'can_join_by_link' => true,
            'confirm_joined' => true,
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
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Pending->value,
        ]);
    }

    #[Test]
    public function user_can_leave_channel_they_joined()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Approved->value,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson(
                route('channel_member.leave', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_OK);
        $channelMember['status'] = ChannelMemberStatus::Left->value;
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);
    }

    #[Test]
    public function channel_managements_can_kick_members()
    {
        $admin = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelAdmin = [
            'channel_id' => $channel->id,
            'profile_id' => $admin->profile->id,
            'role' => ChannelRoles::Admin->value,
            'status' => ChannelMemberStatus::Approved->value,
        ];
        ChannelMember::factory()->state($channelAdmin)->create();
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, $channelAdmin);

        $user = User::factory()->create();
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Approved->value,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                route('channel_member.kick', ['channel' => $channel->id]),
                [
                    'profile_id' => $user->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_OK);

        $channelMember['status'] = ChannelMemberStatus::Left->value;
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);
    }

    #[Test]
    public function users_without_permission_cannot_kick_members()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $channel = Channel::factory()->create();
        foreach ([$userA, $userB] as $member) {
            ChannelMember::factory()->state([
                'channel_id' => $channel->id,
                'profile_id' => $member->profile->id,
                'role' => ChannelRoles::Member->value,
                'status' => ChannelMemberStatus::Approved->value,
            ])->create();
        }
        $this->assertDatabaseCount(ChannelMember::class, 2);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson(
                route('channel_member.kick', ['channel' => $channel->id]),
                [
                    'profile_id' => $userB->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $userB->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Approved->value,
        ]);
    }

    #[Test]
    public function channel_managements_can_block_members()
    {
        $admin = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelAdmin = [
            'channel_id' => $channel->id,
            'profile_id' => $admin->profile->id,
            'role' => ChannelRoles::Admin->value,
            'status' => ChannelMemberStatus::Approved->value,
        ];
        ChannelMember::factory()->state($channelAdmin)->create();
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, $channelAdmin);

        $user = User::factory()->create();
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Approved->value,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                route('channel_member.block', ['channel' => $channel->id]),
                [
                    'profile_id' => $user->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_OK);

        $channelMember['status'] = ChannelMemberStatus::Blocked->value;
        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);
    }

    #[Test]
    public function users_without_permission_cannot_block_members()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $channel = Channel::factory()->create();
        foreach ([$userA, $userB] as $member) {
            ChannelMember::factory()->state([
                'channel_id' => $channel->id,
                'profile_id' => $member->profile->id,
                'role' => ChannelRoles::Member->value,
                'status' => ChannelMemberStatus::Approved->value,
            ])->create();
        }
        $this->assertDatabaseCount(ChannelMember::class, 2);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson(
                route('channel_member.block', ['channel' => $channel->id]),
                [
                    'profile_id' => $userB->profile->id,
                ]
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseCount(ChannelMember::class, 2);
        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $userB->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Approved->value,
        ]);
    }

    #[Test]
    public function blocked_users_cannot_join()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $channelMember = [
            'channel_id' => $channel->id,
            'profile_id' => $user->profile->id,
            'role' => ChannelRoles::Member->value,
            'status' => ChannelMemberStatus::Blocked->value,
        ];
        ChannelMember::factory()->state($channelMember)->create();
        $this->assertDatabaseCount(ChannelMember::class, 1);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(
                route('channel_member.join', ['channel' => $channel->id])
            );
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(ChannelMember::class, 1);
        $this->assertDatabaseHas(ChannelMember::class, $channelMember);
    }

    // user_can_send_join_request

    // all message sending/fetching tests are in MessageTest.php

}
