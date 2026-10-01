<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\MessageableType;
use App\Models\ChannelMember;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    private User $user;

    private Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->profile = $this->user->profile; // assumes ProfileObserver creates profile on user creation
        $this->actingAs($this->user);
    }

    #[Test]
    public function my_chats_returns_empty_when_no_activity(): void
    {
        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    #[Test]
    public function my_chats_includes_conversations(): void
    {
        [$conversation] = $this->createConversation($this->user);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'conversation')
            ->assertJsonPath('data.0.id', $conversation->id);
    }

    #[Test]
    public function my_chats_includes_joined_channels(): void
    {
        [$channel, $owner] = $this->createChannel(member: $this->user);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'channel')
            ->assertJsonPath('data.0.id', $channel->id);
    }

    #[Test]
    public function my_chats_merges_conversations_and_channels(): void
    {
        $this->createConversation($this->user);
        $this->createChannel(member: $this->user);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $types = collect($response->json('data'))->pluck('type')->sort()->values()->all();
        $this->assertSame(['channel', 'conversation'], $types);
    }

    #[Test]
    public function my_chats_sorted_by_most_recent_message_first(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');
        $this->createConversation($this->user);

        Carbon::setTestNow('2025-01-01 12:00:10');
        $this->createChannel(member: $this->user);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertSame('channel', $data[0]['type']);
        $this->assertSame('conversation', $data[1]['type']);
    }

    #[Test]
    public function conversation_item_has_correct_shape(): void
    {
        $this->createConversation($this->user, makeMessage: true);

        $response = $this->getJson(route('chats.my'));

        $item = $response->json('data.0');

        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('profile', $item);
        $this->assertArrayHasKey('handle', $item['profile']);
        $this->assertArrayHasKey('name', $item['profile']);
        $this->assertArrayHasKey('profileable', $item['profile']);
        $this->assertArrayHasKey('featured_picture', $item['profile']);
        $this->assertArrayHasKey('last_message', $item);
        $this->assertArrayHasKey('sender', $item['last_message']);
        $this->assertArrayHasKey('handle', $item['last_message']['sender']);
        $this->assertArrayHasKey('name', $item['last_message']['sender']);
        $this->assertArrayHasKey('profileable', $item['last_message']['sender']);
        $this->assertArrayHasKey('unread_count', $item);
    }

    #[Test]
    public function my_chats_resolves_full_profile_data_with_multiple_conversations(): void
    {
        // to cover the lazyloading prevention
        $otherA = User::factory()->create(['name' => 'Sender A']);
        $otherB = User::factory()->create(['name' => 'Sender B']);

        $this->createConversation($this->user, $otherA, true);   // acting as the sender
        $this->createConversation($otherB, $this->user, true);   // acting as the receiver

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // profile.name requires lowerProfile/higherProfile.profileable to actually resolve
        $names = collect($response->json('data'))->pluck('profile.name')->sort()->values()->all();
        $this->assertSame(['Sender A', 'Sender B'], $names);

        // last_message.sender.name requires lastMessage.sender.profileable to resolve
        foreach ($response->json('data') as $item) {
            $this->assertNotNull($item['last_message']['sender']['name'] ?? null);
        }
    }

    #[Test]
    public function channel_item_has_correct_shape(): void
    {
        $this->createChannel($this->user);

        $response = $this->getJson(route('chats.my'));

        $item = $response->json('data.0');

        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('profile', $item);
        $this->assertArrayHasKey('handle', $item['profile']);
        $this->assertArrayHasKey('last_message', $item);
        $this->assertArrayHasKey('unread_count', $item);
    }

    #[Test]
    public function unread_count_is_zero_when_all_messages_read(): void
    {
        [$conversation] = $this->createConversation($this->user, makeMessage: true);

        // Mark as read by setting last_read to the last message
        $conversation->update([
            'lower_profile_last_read_message_id' => $conversation->last_message_id,
            'higher_profile_last_read_message_id' => $conversation->last_message_id,
        ]);

        $response = $this->getJson(route('chats.my'));

        $this->assertSame(0, $response->json('data.0.unread_count'));
    }

    #[Test]
    public function unread_count_reflects_messages_after_last_read(): void
    {
        $other = User::factory()->create();
        [$conversation] = $this->createConversation($this->user);
        $firstMessageId = $conversation->last_message_id;

        // Send two more messages from the other profile
        Message::factory()->create([
            'sender_id' => $other->profile->id,
            'messageable_type' => MessageableType::Conversation->value,
            'messageable_id' => $conversation->id,
        ]);
        $lastMessage = Message::factory()->create([
            'sender_id' => $other->profile->id,
            'messageable_type' => MessageableType::Conversation->value,
            'messageable_id' => $conversation->id,
        ]);
        $conversation->update(['last_message_id' => $lastMessage->id]);

        // Current user only read up to the first message
        $isLower = $this->profile->id === $conversation->lower_profile_id;
        $conversation->update([
            $isLower
                ? 'lower_profile_last_read_message_id'
                : 'higher_profile_last_read_message_id' => $firstMessageId,
        ]);
        $response = $this->getJson(route('chats.my'));

        $this->assertSame(2, $response->json('data.0.unread_count'));
    }

    #[Test]
    public function channel_unread_count_reflects_messages_after_last_read(): void
    {
        [$channel, $owner] = $this->createChannel();
        $firstMessage = Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);

        // Send two more messages
        Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);
        $lastMessage = Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);
        $channel->update(['last_message_id' => $lastMessage->id]);

        // Current member only read up to the first message
        ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'profile_id' => $this->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
            'role' => ChannelRoles::Member->value,
            'last_read_message_id' => $firstMessage->id,
            'joined_at' => now(),
        ]);

        $response = $this->getJson(route('chats.my'));

        $this->assertSame(2, $response->json('data.0.unread_count'));
    }

    #[Test]
    public function does_not_include_channels_user_has_not_joined(): void
    {
        $this->createChannel();

        $response = $this->getJson(route('chats.my'));

        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function does_not_include_pending_channel_memberships(): void
    {
        [$channel] = $this->createChannel();

        ChannelMember::create([
            'channel_id' => $channel->id,
            'profile_id' => $this->profile->id,
            'status' => ChannelMemberStatus::Pending->value,
            'role' => ChannelRoles::Member->value,
            'joined_at' => now(),
        ]);

        $response = $this->getJson(route('chats.my'));

        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function empty_channel_appears_above_older_conversation_with_messages(): void
    {
        // older conversation with a message.
        Carbon::setTestNow('2025-01-01 13:00:00');
        [$conversation, $user] = $this->createConversation(makeMessage: true);

        // newer empty channel
        Carbon::setTestNow('2026-09-28 12:00:00');
        [$channel] = $this->createChannel(owner: $user);

        $response = $this->actingAs($user)->getJson(route('chats.my'));
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$channel->id, $conversation->id], $ids);
    }
}
