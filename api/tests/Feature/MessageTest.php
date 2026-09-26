<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Enums\MessageableType;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use App\Services\ChannelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([MessageSent::class, MessageRead::class]);
    }

    #[Test]
    public function user_can_message_other_user()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiver_user = User::factory()->create();
        $receiver = $receiver_user->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => 1,
            'is_available_on_receiver' => 1,
            'body' => 'test message',
        ]);
    }

    #[Test]
    public function user_can_message_channels_with_permission()
    {
        $channelOwner = User::factory()->create();
        $ownerProfile = $channelOwner->profile;
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',

        ], $channelOwner);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $ownerProfile->id,
            'is_available_on_sender' => 1,
            'is_available_on_receiver' => 1,
            'body' => 'test message',
        ]);
    }

    #[Test]
    public function sending_message_update_last_message_id()
    {
        // for conversations
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiver_user = User::factory()->create();
        $receiver = $receiver_user->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $messageId = $response->json()['id'];
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $sender->id,
            'higher_profile_id' => $receiver->id,
            'last_message_id' => $messageId,
        ]);

        // for channels
        $channelOwner = User::factory()->create();
        $ownerProfile = $channelOwner->profile;
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',

        ], $channelOwner);
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => null,
        ]);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $messageId = $response->json()['id'];
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => $messageId,
        ]);
    }

    #[Test]
    public function message_removal_update_last_message_id()
    {
        // for conversations
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiver_user = User::factory()->create();
        $receiver = $receiver_user->profile;
        $this->assertDatabaseCount(Message::class, 0);
        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $firstMessageId = $response->json()['id'];
        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ])->assertStatus(Response::HTTP_CREATED);
        $lastMessageId = $response->json()['id'];
        $this->assertDatabaseCount(Message::class, 2);
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $sender->id,
            'higher_profile_id' => $receiver->id,
            'last_message_id' => $lastMessageId,
        ]);

        $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.destroy', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $sender->id,
            'higher_profile_id' => $receiver->id,
            'last_message_id' => $firstMessageId,
        ]);

        $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.destroy', $firstMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $sender->id,
            'higher_profile_id' => $receiver->id,
            'last_message_id' => null,
        ]);

        // for channels
        $channelOwner = User::factory()->create();
        $ownerProfile = $channelOwner->profile;
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',

        ], $channelOwner);
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => null,
        ]);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $firstMessageId = $response->json()['id'];
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => $firstMessageId,
        ]);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $lastMessageId = $response->json()['id'];
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => $lastMessageId,
        ]);

        $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('message.destroy', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => $firstMessageId,
        ]);

        $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('message.destroy', $firstMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_message_id' => null,
        ]);

    }

    #[Test]
    public function cannot_send_empty_message()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;

        $receiver_user = User::factory()->create();
        $receiver = $receiver_user->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => '',
            ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => 1,
            'is_available_on_receiver' => 1,
            'body' => '',
        ]);
        $this->assertDatabaseEmpty(Message::class);

    }

    // user_cannot_send_message_to_user_they_got_blocked
    //
    // cannot_send_message_with_invalid_payload
    // message_creation_persists_correct_attributes
    //

    #[Test]
    public function user_can_fetch_dm_messages()
    {
        $lowerUser = User::factory()->create();
        $lower = $lowerUser->profile;
        $higherUser = User::factory()->create();
        $higher = $higherUser->profile;

        foreach (['one', 'two', 'three', 'four', 'five'] as $messageBody) {
            $this->actingAs($lowerUser, 'sanctum')
                ->postJson(route('message.store'), [
                    'receiver_handle' => $higher->handle,
                    'body' => $messageBody,
                ]);
        }

        $this->assertDatabaseCount(Message::class, 5);
        Message::factory(10)->create();
        $this->assertDatabaseCount(Message::class, 15);

        $response = $this->actingAs($lowerUser, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $higher->handle]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function user_cannot_fetch_others_dm_messages()
    {
        $lowerUser = User::factory()->create();
        $lower = $lowerUser->profile;
        $higherUser = User::factory()->create();
        $higher = $higherUser->profile;
        $conversation = Conversation::factory()->state([
            'lower_profile_id' => $lower->id,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $higher->id,
            'is_available_for_higher_profile' => true,
        ])->create();
        Message::factory(10)->state([
            'sender_id' => $lower->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,

        ])->create();
        Message::factory(10)->state([
            'sender_id' => $higher->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        $this->assertDatabaseCount(Message::class, 20);

        $response = $this->actingAs($lowerUser)
            ->getJson(route('profile.messages.index', ['profile' => $higher->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(20, 'data');

        $newUser = User::factory()->create();
        $response = $this->actingAs($newUser)
            ->getJson(route('profile.messages.index', ['profile' => $higher->handle]));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function user_can_fetch_channels_messages()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',

        ], $channelOwner);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message two',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_can_fetch_private_channels_messages_when_joined()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',
            'visibility' => ChannelVisibility::Private->value,
        ], $channelOwner);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message two',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_cannot_fetch_private_channels_messages_they_have_not_joined()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',
            'visibility' => ChannelVisibility::Private->value,
        ], $channelOwner);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message two',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Pending->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function user_can_fetch_private_channels_messages_they_joined()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',
            'visibility' => ChannelVisibility::Private->value,
        ], $channelOwner);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message two',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Pending->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function channels_messages_fetch_correctly()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',

        ], $channelOwner);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'should be fetched',
            ]);
        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'should be fetched two',
            ]);
        $newChannelOwner = User::factory()->create();
        $newChannelService = app(ChannelService::class);
        $newChannel = $newChannelService->createChannel([
            'name' => 'test newChannel',
            'description' => 'test newChannel description',

        ], $newChannelOwner);
        $this->actingAs($newChannelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $newChannel->profile->handle,
                'body' => 'not to be fetched',
            ]);
        $this->actingAs($newChannelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $newChannel->profile->handle,
                'body' => 'not to be fetched two',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJson([
            'data' => [
                [
                    'body' => 'should be fetched two',
                ], [
                    'body' => 'should be fetched',
                ],
            ],
        ]);
    }

    #[Test]
    public function channels_fetched_messages_does_not_include_removed()
    {
        $channelOwner = User::factory()->create();
        $channelService = app(ChannelService::class);
        $channel = $channelService->createChannel([
            'name' => 'test channel',
            'description' => 'test channel description',
        ], $channelOwner);

        $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'should be fetched',
            ]);
        $message = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'not to be fetched',
            ]);

        $member = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ])->create();

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');

        $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('message.destroy', $message->json('id')));

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                [
                    'body' => 'should be fetched',
                ],
            ],
        ]);
    }

    #[Test]
    public function user_search_fetch_correct_messages()
    {
        $lowerUser = User::factory()->create();
        $lower = $lowerUser->profile;
        $higherUser = User::factory()->create();
        $higher = $higherUser->profile;

        $conversation = Conversation::factory()->state([
            'lower_profile_id' => $lower->id,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $higher->id,
            'is_available_for_higher_profile' => true,
        ])->create();

        Message::factory(10)->state([
            'sender_id' => $lower->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        Message::factory(10)->state([
            'sender_id' => $higher->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        Message::factory(1)->state([
            'sender_id' => $lower->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
            'body' => 'should be fetched as includes searchedKeyword, send by lower',
        ])->create();
        Message::factory(1)->state([
            'sender_id' => $higher->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
            'body' => 'should be fetched as includes searchedKeyword, send by higher',
        ])->create();

        $this->assertDatabaseCount(Message::class, 22);
        Message::factory(10)->create();
        $this->assertDatabaseCount(Message::class, 32);

        $response = $this->actingAs($lowerUser, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $higher->handle, 'search' => 'searchedKeyword']));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJson([
            'data' => [
                [
                    'sender' => ['handle' => $higher->handle],
                    'body' => 'should be fetched as includes searchedKeyword, send by higher',
                ],
                [
                    'sender' => ['handle' => $lower->handle],
                    'body' => 'should be fetched as includes searchedKeyword, send by lower',
                ],
            ],
        ]);
    }


    #[Test]
    public function users_cannot_fetch_messages_from_other_conversations()
    {
        $participantA = User::factory()->create();
        $participantB = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($participantA, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $participantB->profile->handle,
                'body' => 'private message',
            ])->assertStatus(Response::HTTP_CREATED);

        // No conversation exists between stranger and participantB
        $this->actingAs($stranger, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $participantB->profile->handle]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function users_cannot_fetch_messages_from_other_conversations_search()
    {
        $participantA = User::factory()->create();
        $participantB = User::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($participantA, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $participantB->profile->handle,
                'body' => 'top secret keyword',
            ])->assertStatus(Response::HTTP_CREATED);

        // Even knowing the exact keyword, an outsider gets 404
        $this->actingAs($stranger, 'sanctum')
            ->getJson(route('profile.messages.index', [
                'profile' => $participantB->profile->handle,
                'search' => 'secret',
            ]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    // Updating (if your app allows editing messages)
    #[Test]
    public function user_can_edit_own_message()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'body' => 'original message',
        ]);
        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'body' => 'original message',
        ]);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->putJson(route('message.update', $message->id), [
                'body' => 'updated message',
            ]);
        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'body' => 'updated message',
        ]);
        $this->assertDatabaseMissing(Message::class, [
            'sender_id' => $message->id,
            'body' => 'original message',
        ]);
    }

    #[Test]
    public function user_cannot_edit_own_old_message()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'body' => 'original message',
            'created_at' => Carbon::now()->subHours(3),
        ]);
        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'body' => 'original message',
        ]);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->putJson(route('message.update', $message->id), [
                'body' => 'updated message',
            ]);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'body' => 'original message',
        ]);
        $this->assertDatabaseMissing(Message::class, [
            'sender_id' => $message->id,
            'body' => 'updated message',
        ]);
    }

    #[Test]
    public function user_cannot_edit_message_they_did_not_create()
    {
        $message = Message::factory()->create();
        $this->assertDatabaseCount(Message::class, 1);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson(route('message.update', $message->id), [
                'body' => 'updated message',
            ]);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $message->sender_id,
        ]);
        $this->assertDatabaseCount(Message::class, 1);
    }

    //    test_cannot_edit_deleted_message (if soft deletes)

    // Deleting

    #[Test]
    public function user_can_delete_own_message_before_seen_in_conversation()
    {
        $senderUser = User::factory()->create();
        $receiver_user = User::factory()->create();
        $receiver = $receiver_user->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $messageId = $response->json()['id'];
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.destroy', $messageId));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted(Message::class, [
            'id' => $messageId,
        ]);
        $this->assertDatabaseCount(Message::class, 1); // it's a softDelete
    }

    #[Test]
    public function user_cannot_delete_others_messages()
    {
        $senderUser = User::factory()->create();

        $message = Message::factory()->create();
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.destroy', $message->id));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNotSoftDeleted(Message::class, [
            'id' => $message->id,
        ]);
        $this->assertDatabaseCount(Message::class, 1); // it's a softDelete
    }

    #[Test]
    public function user_cannot_delete_own_message_after_seen()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;

        $message = Message::factory()->create([
            'sender_id' => $sender->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.destroy', $message->id));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNotSoftDeleted(Message::class, [
            'id' => $message->id,
        ]);
    }

    #[Test]
    public function user_can_hide_associated_message_as_sender()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiverUser = User::factory()->create();
        $receiver = $receiverUser->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);

        $message = $response->json();

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
            'body' => 'test message',
        ]);

        $response = $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => false,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
            'body' => 'test message',
        ]);
        $this->assertDatabaseCount(Message::class, 1);
    }

    #[Test]
    public function user_can_hide_associated_message_as_receiver()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiver = User::factory()->create();

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->profile->handle,
                'body' => 'test message',
            ]);

        $message = $response->json();

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
        ]);

        $response = $this->actingAs($receiver, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message['id'],
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => false,
        ]);
        $this->assertDatabaseCount(Message::class, 1);
    }

    #[Test]
    public function user_can_hide_associated_message_both_plus_soft_delete()
    {
        $senderUser = User::factory()->create();
        $sender = $senderUser->profile;
        $receiverUser = User::factory()->create();
        $receiver = $receiverUser->profile;

        $response = $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->handle,
                'body' => 'test message',
            ]);

        $message = $response->json();

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
        ]);

        // hiding as sender
        $response = $this->actingAs($senderUser, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $sender->id,
            'is_available_on_sender' => false,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->actingAs($receiverUser, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message['id'],
            'is_available_on_sender' => false,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => false,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $this->assertSoftDeleted(Message::class, [
            'id' => $message['id'],
        ]);

    }

    #[Test]
    public function unrelated_users_cannot_change_message_availability()
    {
        $message = Message::factory()->create([
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,

        ]);

        $createdMessage = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'is_available_on_sender' => $message->is_available_on_sender,
            'messageable_type' => $message->messageable_type,
            'messageable_id' => $message->messageable_id,
            'is_available_on_receiver' => $message->is_available_on_receiver,
            'body' => $message->body,
        ];

        $this->assertDatabaseCount(Message::class, 1);
        $this->assertDatabaseHas(Message::class, $createdMessage);

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas(Message::class, $createdMessage);
        $this->assertDatabaseCount(Message::class, 1);
    }

    // Attachments/Medias
    //
    //    test_user_can_send_message_with_attachment
    //    test_attachment_must_be_valid_file_type
    //    test_attachment_upload_stores_file_correctly
    //    test_message_with_attachment_returns_file_metadata
    //

    #[Test]
    public function sending_a_message_dispatches_message_sent_event()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $this->actingAs($sender, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiver->profile->handle,
                'body' => 'Hello there',
            ])
            ->assertStatus(Response::HTTP_CREATED);

        Event::assertDispatched(MessageSent::class, function ($event) use ($sender) {
            return $event->message->sender_id === $sender->profile->id
                && $event->message->body === 'Hello there';
        });
    }

    #[Test]
    public function mark_conversation_message_as_read_dispatches_read_and_update_last_read_message_correctly()
    {
        $sender = User::factory()->create();
        $reader = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $reader->profile->id,
            'lower_profile_last_read_message_id' => 1,
            'higher_profile_last_read_message_id' => 1,
        ]);
        $message = Message::factory()->create([
            'messageable_type' => MessageableType::Conversation->value,
            'messageable_id' => $conversation->id,

        ]);
        $seedingMessageId = $message->id;
        $conversation->update([
            'lower_profile_last_read_message_id' => $seedingMessageId,
            'higher_profile_last_read_message_id' => $seedingMessageId,
        ]);

        $messageResponse = $this->actingAs($sender, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $reader->profile->handle,
                'body' => 'Hello there',
            ]);
        $messageResponse->assertStatus(Response::HTTP_CREATED);
        $messageId = $messageResponse->json('id');
        $this->assertDatabaseHas(Message::class, [
            'id' => $messageId,
            'sender_id' => $sender->profile->id,
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,
            'is_read' => false,
        ]);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $reader->profile->id,
            'lower_profile_last_read_message_id' => $seedingMessageId,
            'higher_profile_last_read_message_id' => $seedingMessageId,
        ]);

        $this->actingAs($reader, 'sanctum')
            ->postJson(route('message.read', ['message' => $messageId]))
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas(Message::class, [
            'id' => $messageId,
            'sender_id' => $sender->profile->id,
            'is_available_on_receiver' => true,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $reader->profile->id,
            'lower_profile_last_read_message_id' => $seedingMessageId,
            'higher_profile_last_read_message_id' => $messageId,
        ]);

        Event::assertDispatched(MessageRead::class, function ($event) use ($reader, $messageId) {
            return $event->message->id === $messageId
                && $event->readerProfile->id === $reader->profile->id;
        });
    }

    #[Test]
    public function user_can_mark_their_channel_message_as_read()
    {

        $channel = Channel::factory()->create([
            'type' => ChannelType::Group->value,
        ]);
        $message = Message::factory()->create([
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);

        $member = User::factory()->create();
        $channel->members()->create([
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ]);

        $this->actingAs($member, 'sanctum')
            ->postJson(route('message.read', ['message' => $message->id]))
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $member->profile->id,
            'last_read_message_id' => $message->id,
        ]);
    }

    #[Test]
    public function read_and_unread_messages_are_flagged_correctly()
    {
        $sender = User::factory()->create();
        $reciever = User::factory()->create();

        $toNotRead = $this->actingAs($sender, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $reciever->profile->handle,
                'body' => 'stays unread',
            ])->json('id');

        $toRead = $this->actingAs($sender, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $reciever->profile->handle,
                'body' => 'gets read',
            ])->json('id');

        // marking $toRead as read
        $this->actingAs($reciever, 'sanctum')
            ->postJson(route('message.read', ['message' => $toRead]))
            ->assertStatus(Response::HTTP_OK);

        // per design, only receiver can mark a message as read, and only sender can see if it's read
        $response = $this->actingAs($reciever, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $sender->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertNull($byId[$toRead]['is_read']);
        $this->assertNull($byId[$toNotRead]['is_read']);


        $response = $this->actingAs($sender, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $reciever->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($byId[$toRead]['is_read']);
        $this->assertFalse($byId[$toNotRead]['is_read']);
    }

    #[Test]
    public function message_cannot_be_marked_as_read_by_unauthorized_user()
    {

        Message::factory(10)->create();
        $conversation = Conversation::factory()->create([
            'lower_profile_last_read_message_id' => 1,
            'higher_profile_last_read_message_id' => 1,
        ]);
        $message = Message::factory()->create([
            'messageable_type' => MessageableType::Conversation->value,
            'messageable_id' => $conversation->id,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson(route('message.read', ['message' => $message->id]))
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_last_read_message_id' => 1,
            'higher_profile_last_read_message_id' => 1,
        ]);
    }


    #[Test]
    public function messages_are_ordered_chronologically()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        foreach (['first', 'second', 'third', 'fourth', 'fifth'] as $body) {
            $this->actingAs($userA, 'sanctum')
                ->postJson(route('message.store'), [
                    'receiver_handle' => $userB->profile->handle,
                    'body' => $body,
                ])->assertStatus(Response::HTTP_CREATED);
        }

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $userB->profile->handle]));

        $response->assertStatus(Response::HTTP_OK);

        // newest first
        $response->assertJsonPath('data.0.body', 'fifth');
        $response->assertJsonPath('data.4.body', 'first');

        // id must be descending
        $ids = collect($response->json('data'))->pluck('id')->all();
        $sorted = $ids;
        rsort($sorted);
        $this->assertSame($sorted, $ids);
    }

    #[Test]
    public function message_pagination_works()
    {
        $beforeCount = config('app.messages_count_before_anchor_for_pagination', 10);
        $afterCount = config('app.messages_count_after_anchor_for_pagination', 30);
        $totalCount = $beforeCount + $afterCount;
        $extraMessages = 20;

        $lowerUser = User::factory()->create();
        $higherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $lowerUser->profile->id,
            'higher_profile_id' => $higherUser->profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);

        Message::factory($totalCount + $extraMessages)->state([
            'sender_id' => $lowerUser->profile->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        // request with no anchor must newest messages (page) with has_more_before=true
        $response = $this->actingAs($lowerUser, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $higherUser->profile->handle]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount($totalCount, 'data');
        $response->assertJsonPath('meta.has_more_before', true);
        $response->assertJsonPath('meta.has_more_after', false);
        $response->assertJsonPath('data.0.id', Message::max('id'));

        // request with anchor id, providing an id that can get both has_more sides true
        $allIds = Message::orderBy('id')->pluck('id');
        $anchor = $allIds[$beforeCount + intdiv($extraMessages, 2)];

        $response = $this->actingAs($lowerUser, 'sanctum')
            ->getJson(route('profile.messages.index', [
                'profile' => $higherUser->profile->handle,
                'anchor_message_id' => $anchor,
            ]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonPath('meta.has_more_before', true);
        $response->assertJsonPath('meta.has_more_after', true);
        // anchor message must be in the payload
        $this->assertContains($anchor, collect($response->json('data'))->pluck('id')->all());
    }


}
