<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelVisibility;
use App\Enums\MessageableType;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    private User $user;

    protected static string $default_time = '2025-01-01 12:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Event::fake([MessageSent::class, MessageRead::class]);

        Carbon::setTestNow(self::$default_time);
    }

    #[Test]
    public function user_can_message_other_user()
    {
        $receiverUser = User::factory()->create();

        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiverUser->profile->handle,
            'body' => 'test message',
        ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $this->user->profile->id,
            'is_available_on_sender' => 1,
            'is_available_on_receiver' => 1,
            'body' => 'test message',
        ]);
    }

    #[Test]
    public function user_with_permission_can_message_channels()
    {
        [$channel, $owner] = $this->createChannel();
        $response = $this->actingAs($owner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Message::class, [
            'sender_id' => $owner->profile->id,
            'is_available_on_sender' => 1,
            'is_available_on_receiver' => 1,
            'body' => 'test message',
        ]);
    }

    #[Test]
    public function sending_message_update_last_message_id()
    {
        // for conversations
        [, , $receiver] = $this->createConversation($this->user, makeMessage: true);
        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'test message',
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $messageId = $response->json()['id'];
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $this->user->profile->id,
            'higher_profile_id' => $receiver->profile->id,
            'last_message_id' => $messageId,
        ]);

        // for channels
        [$channel, $channelOwner] = $this->createChannel();
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
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
        [$conversation, , $receiver] = $this->createConversation($this->user);
        $this->assertDatabaseCount(Message::class, 0);

        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'test message',
        ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $firstMessageId = $response->json()['id'];

        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'test message',
        ]);
        $lastMessageId = $response->json()['id'];
        $this->assertDatabaseCount(Message::class, 2);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'lower_profile_id' => $this->user->profile->id,
            'higher_profile_id' => $receiver->profile->id,
            'last_message_id' => $lastMessageId,
        ]);

        $this->deleteJson(route('message.destroy', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_message_id' => $firstMessageId,
        ]);

        $this->deleteJson(route('message.destroy', $firstMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_message_id' => null,
        ]);

        // for channels
        [$channel, $channelOwner] = $this->createChannel();
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
        ]);
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
    public function sending_message_update_last_activity_at()
    {
        // for conversations
        [$conversation, , $higherUser] = $this->createConversation($this->user);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        $newTime = '2026-09-27 12:00:00';
        Carbon::setTestNow($newTime);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $higherUser->profile->handle,
            'body' => 'test message',
        ])->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse($newTime),
        ]);

        // for channels
        Carbon::setTestNow(self::$default_time);
        [$channel, $channelOwner] = $this->createChannel();
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        Carbon::setTestNow($newTime);
        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_activity_at' => Carbon::parse($newTime),
        ]);
    }

    #[Test]
    public function message_removal_updates_last_activity_at()
    {
        // for conversations
        [$conversation, , $higherUser] = $this->createConversation($this->user);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        $lastMessageTime = '2026-09-27 12:00:00';
        Carbon::setTestNow($lastMessageTime);
        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $higherUser->profile->handle,
            'body' => 'test message',
        ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse($lastMessageTime),
        ]);

        $lastMessageId = $response->json()['id'];

        // message removed, not hide for a side; it must be updated
        $this->deleteJson(route('message.destroy', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        // for channels
        Carbon::setTestNow(self::$default_time);
        [$channel, $channelOwner] = $this->createChannel();
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        Carbon::setTestNow($lastMessageTime);

        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $channel->profile->handle,
                'body' => 'test message',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);
        $lastMessageId = $response->json()['id'];

        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_activity_at' => Carbon::parse($lastMessageTime),
        ]);

        $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('message.destroy', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertDatabaseHas(Channel::class, [
            'id' => $channel->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);
    }

    #[Test]
    public function message_hiding_does_not_update_last_activity_at()
    {
        // for conversations
        [$conversation, , $higherUser] = $this->createConversation($this->user);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse(self::$default_time),
        ]);

        $lastMessageTime = '2026-09-27 12:00:00';
        Carbon::setTestNow($lastMessageTime);
        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $higherUser->profile->handle,
            'body' => 'test message',
        ])->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse($lastMessageTime),
        ]);
        $lastMessageId = $response->json()['id'];

        // hiding for lowerUser's side, the activity time must not be changed
        $this->deleteJson(route('message.hide', $lastMessageId))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'last_activity_at' => Carbon::parse($lastMessageTime),
        ]);

        // Channel does not have hiding option, messages are either visible for all member or deleted!
    }

    #[Test]
    public function cannot_send_empty_message()
    {
        [$conversation, , $receiver] = $this->createConversation($this->user);

        $response = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->handle,
            'body' => '',
        ]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing(Message::class, [
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
            'body' => '',
        ]);
        $this->assertDatabaseEmpty(Message::class);
    }

    #[Test]
    public function failed_message_creation_does_not_change_last_activity_at(): void
    {
        [$conversation] = $this->createConversation($this->user);
        $original = $conversation->last_activity_at->copy();

        $this->postJson(route('message.store'),
            ['body' => '']
        )->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertTrue($conversation->fresh()->last_activity_at->equalTo($original));
    }

    // user_cannot_send_message_to_user_they_got_blocked
    //
    // cannot_send_message_with_invalid_payload
    // message_creation_persists_correct_attributes
    //

    #[Test]
    public function user_can_fetch_dm_messages()
    {
        [$conversation, , $receiver] = $this->createConversation($this->user);

        foreach (['one', 'two', 'three', 'four', 'five'] as $messageBody) {
            $this->postJson(route('message.store'), [
                'receiver_handle' => $receiver->profile->handle,
                'body' => $messageBody,
            ]);
        }

        $this->assertDatabaseCount(Message::class, 5);
        Message::factory(10)->create();
        $this->assertDatabaseCount(Message::class, 15);

        $response = $this->getJson(route('profile.messages.index', ['profile' => $receiver->profile->handle]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
    }

    #[Test]
    public function user_cannot_fetch_others_dm_messages()
    {
        [$conversation, , $receiver] = $this->createConversation($this->user);

        // 10 messages as sender
        Message::factory(10)->state([
            'sender_id' => $this->user->profile->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();
        // and 10 messages as receiver
        Message::factory(10)->state([
            'sender_id' => $receiver->profile->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        // 20 messages for others
        Message::factory(20)->create();

        $this->assertDatabaseCount(Message::class, 40);

        $response = $this->getJson(route('profile.messages.index', ['profile' => $receiver->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(20, 'data');

        $newUser = User::factory()->create();
        $response = $this->actingAs($newUser)
            ->getJson(route('profile.messages.index', ['profile' => $receiver->profile->handle]));
        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function user_can_fetch_channels_messages()
    {
        $member = User::factory()->create();
        [$channel, $channelOwner] = $this->createChannel(member: $member);
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

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_can_fetch_private_channels_messages_they_joined()
    {
        $member = User::factory()->create();
        [$channel] = $this->createChannel($this->user, member: $member, channelVisibility: ChannelVisibility::Private);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message',
        ]);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message two',
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
    }

    #[Test]
    public function user_cannot_fetch_private_channels_messages_they_have_not_joined()
    {
        [$channel] = $this->createChannel($this->user, channelVisibility: ChannelVisibility::Private);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message',
        ]);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message two',
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function pending_members_cannot_fetch_private_channels_messages()
    {
        [$channel] = $this->createChannel($this->user, channelVisibility: ChannelVisibility::Private);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message',
        ]);
        $this->postJson(route('message.store'), [
            'receiver_handle' => $channel->profile->handle,
            'body' => 'test message two',
        ]);

        $pendingMember = User::factory()->create();
        ChannelMember::factory()->state([
            'channel_id' => $channel->id,
            'profile_id' => $pendingMember->profile->id,
            'status' => ChannelMemberStatus::Pending->value,
        ])->create();

        $response = $this->actingAs($pendingMember, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function channels_messages_fetch_correctly()
    {
        $member = User::factory()->create();
        [$channel, $channelOwner] = $this->createChannel(member: $member);
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

        [$newChannel, $newChannelOwner] = $this->createChannel();
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
        $this->assertDatabaseCount(Message::class, 4);

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
    public function channels_fetched_messages_does_not_include_removed_ones()
    {
        [$channel, $channelOwner] = $this->createChannel(member: $this->user);

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

        $response = $this->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');

        $this->actingAs($channelOwner, 'sanctum')
            ->deleteJson(route('message.destroy', $message->json('id')));

        $response = $this->getJson(route('profile.messages.index', ['profile' => $channel->profile->handle]));
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
        [$conversation, , $userB] = $this->createConversation($this->user);
        $profileA = $this->user->profile;
        $profileB = $userB->profile;

        Message::factory(10)->state([
            'sender_id' => $profileA->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        Message::factory(10)->state([
            'sender_id' => $profileB->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        Message::factory(1)->state([
            'sender_id' => $profileA->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
            'body' => 'should be fetched as includes searchedKeyword, send by lower',
        ])->create();
        Message::factory(1)->state([
            'sender_id' => $profileB->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
            'body' => 'should be fetched as includes searchedKeyword, send by higher',
        ])->create();

        $this->assertDatabaseCount(Message::class, 22);
        Message::factory(10)->create();
        $this->assertDatabaseCount(Message::class, 32);

        $response = $this->getJson(route('profile.messages.index', ['profile' => $profileB->handle, 'search' => 'searchedKeyword']));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJson([
            'data' => [
                [
                    'sender' => ['handle' => $profileB->handle],
                    'body' => 'should be fetched as includes searchedKeyword, send by higher',
                ],
                [
                    'sender' => ['handle' => $profileA->handle],
                    'body' => 'should be fetched as includes searchedKeyword, send by lower',
                ],
            ],
        ]);
    }

    #[Test]
    public function users_cannot_fetch_messages_from_other_conversations()
    {
        [, , $userB] = $this->createConversation($this->user, makeMessage: true);

        $this->getJson(route('profile.messages.index', ['profile' => $userB->profile->handle]))
            ->assertStatus(Response::HTTP_OK);

        $stranger = User::factory()->create();
        // No conversation exists between stranger and $userB
        $this->actingAs($stranger, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $userB->profile->handle]))
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[Test]
    public function users_cannot_fetch_messages_from_other_conversations_search()
    {
        [, , $userB] = $this->createConversation($this->user, makeMessage: true);
        $stranger = User::factory()->create();

        $this->postJson(route('message.store'), [
            'receiver_handle' => $userB->profile->handle,
            'body' => 'top secret keyword',
        ])->assertStatus(Response::HTTP_CREATED);

        $this->getJson(route('profile.messages.index', [
            'profile' => $userB->profile->handle,
            'search' => 'secret',
        ]))
            ->assertStatus(Response::HTTP_OK)->assertJsonCount(1, 'data');

        // Even knowing the exact keyword, an outsider gets 404
        $this->actingAs($stranger, 'sanctum')
            ->getJson(route('profile.messages.index', [
                'profile' => $userB->profile->handle,
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
        [, , , $message] = $this->createConversation($this->user, makeMessage: true);
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->deleteJson(route('message.destroy', $message->id));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted(Message::class, [
            'id' => $message->id,
        ]);
        $this->assertDatabaseCount(Message::class, 1); // it's a softDelete
    }

    #[Test]
    public function user_cannot_delete_others_messages()
    {
        $message = Message::factory()->create();
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->deleteJson(route('message.destroy', $message->id));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNotSoftDeleted(Message::class, [
            'id' => $message->id,
        ]);
        $this->assertDatabaseCount(Message::class, 1); // it's a softDelete
    }

    #[Test]
    public function user_cannot_delete_own_message_after_seen()
    {

        $message = Message::factory()->create([
            'sender_id' => $this->user->profile->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $response = $this->deleteJson(route('message.destroy', $message->id));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNotSoftDeleted(Message::class, [
            'id' => $message->id,
        ]);
    }

    #[Test]
    public function user_can_hide_associated_message_as_sender()
    {
        [, , , $message] = $this->createConversation($this->user, makeMessage: true);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'sender_id' => $this->user->profile->id,
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,
        ]);

        $this->deleteJson(route('message.hide', $message['id']))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'sender_id' => $this->user->profile->id,
            'is_available_on_sender' => false,
            'is_available_on_receiver' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);
    }

    #[Test]
    public function user_can_hide_associated_message_as_receiver()
    {
        [, , $receiver, $message] = $this->createConversation($this->user, makeMessage: true);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'sender_id' => $this->user->profile->id,
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => true,
        ]);

        $response = $this->actingAs($receiver, 'sanctum')
            ->deleteJson(route('message.hide', $message->id));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'is_available_on_sender' => true,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => false,
        ]);
        $this->assertDatabaseCount(Message::class, 1);
    }

    #[Test]
    public function messages_hidden_by_both_sender_and_receiver_get_deleted()
    {
        [, , $receiver, $message] = $this->createConversation($this->user, makeMessage: true);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'is_available_on_sender' => true,
            'is_available_on_receiver' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        // hiding as sender
        $this->deleteJson(route('message.hide', $message['id']))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'is_available_on_sender' => false,
            'is_available_on_receiver' => true,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $this->actingAs($receiver, 'sanctum')
            ->deleteJson(route('message.hide', $message['id']))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'is_available_on_sender' => false,
            'messageable_type' => MessageableType::Conversation->value,
            'is_available_on_receiver' => false,
        ]);
        $this->assertDatabaseCount(Message::class, 1);

        $this->assertSoftDeleted(Message::class, [
            'id' => $message->id,
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
        $sender = $this->user;
        $receiver = User::factory()->create();

        $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'Hello there',
        ])->assertStatus(Response::HTTP_CREATED);

        Event::assertDispatched(MessageSent::class, function ($event) use ($sender) {
            return $event->message->sender_id === $sender->profile->id
                && $event->message->body === 'Hello there';
        });
    }

    #[Test]
    public function mark_conversation_message_as_read_dispatches_read_and_update_last_read_message_correctly()
    {
        $sender = $this->user;
        [$conversation, , $reader, $message] = $this->createConversation($sender, makeMessage: true);

        $starterMessageId = $message->id;
        $conversation->update([
            'lower_profile_last_read_message_id' => $starterMessageId,
            'higher_profile_last_read_message_id' => $starterMessageId,
        ]);
        $conversation->fresh();

        $messageResponse = $this->postJson(route('message.store'), [
            'receiver_handle' => $reader->profile->handle,
            'body' => 'Hello there',
        ]);
        $messageResponse->assertStatus(Response::HTTP_CREATED);
        $messageId = $messageResponse->json('id');
        $this->assertDatabaseHas(Message::class, [
            'id' => $messageId,
            'sender_id' => $sender->profile->id,
            'is_read' => false,
        ]);
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $reader->profile->id,
            'lower_profile_last_read_message_id' => $messageId, // after a message sent, the sender's anchor changes to its last message sent
            'higher_profile_last_read_message_id' => $starterMessageId,
        ]);

        $this->actingAs($reader, 'sanctum')
            ->postJson(route('message.read', ['message' => $messageId]))
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas(Message::class, [
            'id' => $messageId,
            'sender_id' => $sender->profile->id,
            'is_read' => true,
        ]);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation->id,
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $reader->profile->id,
            'lower_profile_last_read_message_id' => $messageId,
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
        [$channel] = $this->createChannel(member: $this->user);

        $message = Message::factory()->create([
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);

        $this->postJson(route('message.read', ['message' => $message->id]))
            ->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas(ChannelMember::class, [
            'channel_id' => $channel->id,
            'profile_id' => $this->user->profile->id,
            'last_read_message_id' => $message->id,
        ]);
    }

    #[Test]
    public function read_and_unread_messages_are_flagged_correctly()
    {
        [, $sender, $receiver] = $this->createConversation($this->user);

        $toRead = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'gets read',
        ])->assertCreated()->json('id');

        $toNotRead = $this->postJson(route('message.store'), [
            'receiver_handle' => $receiver->profile->handle,
            'body' => 'stays unread',
        ])->assertCreated()->json('id');

        // marking $toRead as read
        $this->actingAs($receiver, 'sanctum')
            ->postJson(route('message.read', ['message' => $toRead]))
            ->assertStatus(Response::HTTP_OK);

        // per design, only receiver can mark a message as read, and only sender can see if it's read
        $response = $this->actingAs($receiver, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $sender->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertNull($byId[$toRead]['is_read']);
        $this->assertNull($byId[$toNotRead]['is_read']);

        $response = $this->actingAs($sender, 'sanctum')
            ->getJson(route('profile.messages.index', ['profile' => $receiver->profile->handle]));
        $response->assertStatus(Response::HTTP_OK);
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($byId[$toRead]['is_read']);
        $this->assertFalse($byId[$toNotRead]['is_read']);
    }

    #[Test]
    public function message_cannot_be_marked_as_read_by_unauthorized_user()
    {
        [$conversation, , , $message] = $this->createConversation(makeMessage: true);

        $this->postJson(route('message.read', ['message' => $message->id]))
            ->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas(Message::class, [
            'id' => $message->id,
            'is_read' => false,
        ]);
    }

    #[Test]
    public function messages_are_ordered_chronologically()
    {
        [, , $userB] = $this->createConversation($this->user);

        foreach (['first', 'second', 'third', 'fourth', 'fifth'] as $body) {
            $this->postJson(route('message.store'), [
                'receiver_handle' => $userB->profile->handle,
                'body' => $body,
            ])->assertStatus(Response::HTTP_CREATED);
        }

        $response = $this->getJson(route('profile.messages.index', ['profile' => $userB->profile->handle]));
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

        [$conversation, $lowerUser, $higherUser] = $this->createConversation($this->user);

        Message::factory($totalCount + $extraMessages)->state([
            'sender_id' => $lowerUser->profile->id,
            'messageable_id' => $conversation->id,
            'messageable_type' => MessageableType::Conversation->value,
        ])->create();

        // request with no anchor must newest messages (page) with has_more_before=true
        $response = $this->getJson(route('profile.messages.index', ['profile' => $higherUser->profile->handle]));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount($totalCount, 'data');
        $response->assertJsonPath('meta.has_more_before', true);
        $response->assertJsonPath('meta.has_more_after', false);
        $response->assertJsonPath('data.0.id', Message::max('id'));

        // request with anchor id, providing an id that can get both has_more sides true
        $allIds = Message::orderBy('id')->pluck('id');
        $anchor = $allIds[$beforeCount + intdiv($extraMessages, 2)];

        $response = $this->getJson(route('profile.messages.index', [
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
