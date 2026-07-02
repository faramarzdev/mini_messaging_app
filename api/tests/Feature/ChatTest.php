<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelRoles;
use App\Enums\MessageableType;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use App\Services\ChannelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Profile $profile;

    private ChannelService $channelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->channelService = app(ChannelService::class);
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
        $other = User::factory()->create();
        $conversation = $this->createConversationWithMessage($this->profile, $other->profile);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'conversation')
            ->assertJsonPath('data.0.id', $conversation->id);
    }

    #[Test]
    public function my_chats_includes_joined_channels(): void
    {
        $channel = $this->createChannelWithMember($this->profile);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'channel')
            ->assertJsonPath('data.0.id', $channel->id);
    }

    #[Test]
    public function my_chats_merges_conversations_and_channels(): void
    {
        $other = User::factory()->create();
        $this->createConversationWithMessage($this->profile, $other->profile);
        $this->createChannelWithMember($this->profile);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $types = collect($response->json('data'))->pluck('type')->sort()->values()->all();
        $this->assertSame(['channel', 'conversation'], $types);
    }

    // ─── Sorting tests ────────────────────────────────────────────────────────

    #[Test]
    public function my_chats_sorted_by_most_recent_message_first(): void
    {
        $other = User::factory()->create();

        // Create conversation first, channel second — channel should appear first since it has a higher last_message_id
        $conversation = $this->createConversationWithMessage($this->profile, $other->profile);
        $channel = $this->createChannelWithMember($this->profile, withMessage: true);

        $response = $this->getJson(route('chats.my'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertSame('channel', $data[0]['type']);
        $this->assertSame('conversation', $data[1]['type']);
    }

    // ─── Response shape tests ─────────────────────────────────────────────────

    #[Test]
    public function conversation_item_has_correct_shape(): void
    {
        $other = User::factory()->create();
        $this->createConversationWithMessage($this->profile, $other->profile);

        $response = $this->getJson(route('chats.my'));

        $item = $response->json('data.0');

        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('profile', $item);
        $this->assertArrayHasKey('handle', $item['profile']);
        $this->assertArrayHasKey('picture_url', $item['profile']);
        $this->assertArrayHasKey('last_message', $item);
        $this->assertArrayHasKey('unread_count', $item);
    }

    #[Test]
    public function channel_item_has_correct_shape(): void
    {
        $this->createChannelWithMember($this->profile, withMessage: true);

        $response = $this->getJson(route('chats.my'));

        $item = $response->json('data.0');

        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('profile', $item);
        $this->assertArrayHasKey('handle', $item['profile']);
        $this->assertArrayHasKey('last_message', $item);
        $this->assertArrayHasKey('unread_count', $item);
    }

    // ─── Unread count tests ───────────────────────────────────────────────────

    #[Test]
    public function unread_count_is_zero_when_all_messages_read(): void
    {
        $other = User::factory()->create();
        $conversation = $this->createConversationWithMessage($this->profile, $other->profile);

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
        $conversation = $this->createConversationWithMessage($this->profile, $other->profile);
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
        $owner = User::factory()->create();
        $channel = $this->channelService->createChannel([
            'name' => 'Test Channel',
            'visibility' => 'public',
            'type' => 'group',
        ], $owner);
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

    // ─── Exclusion tests ──────────────────────────────────────────────────────

    #[Test]
    public function does_not_include_channels_user_has_not_joined(): void
    {
        // Channel exists but user is not a member
        $owner = User::factory()->create();
        $this->channelService->createChannel([
            'name' => 'Some Channel',
            'visibility' => 'public',
        ], $owner);

        $response = $this->getJson(route('chats.my'));

        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function does_not_include_pending_channel_memberships(): void
    {
        $owner = User::factory()->create();
        $channel = $this->channelService->createChannel([
            'name' => 'Private Channel',
            'visibility' => 'private',
            'type' => 'group',
        ], $owner);

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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createConversationWithMessage(Profile $profileA, Profile $profileB): Conversation
    {
        [$lower, $higher] = Conversation::normalizeProfiles($profileA->id, $profileB->id);

        $conversation = Conversation::create([
            'lower_profile_id' => $lower,
            'higher_profile_id' => $higher,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
            'lower_profile_last_read_message_id' => null,
            'higher_profile_last_read_message_id' => null,
        ]);

        $message = Message::factory()->create([
            'sender_id' => $profileA->id,
            'messageable_type' => MessageableType::Conversation->value,
            'messageable_id' => $conversation->id,
        ]);

        $conversation->update(['last_message_id' => $message->id]);

        return $conversation->fresh();
    }

    private function createChannelWithMember(Profile $profile, bool $withMessage = false): Channel
    {
        $owner = User::factory()->create();
        $channel = $this->channelService->createChannel([
            'name' => 'Test Channel',
            'visibility' => 'public',
            'type' => 'group',
        ], $owner);

        ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'profile_id' => $profile->id,
            'status' => ChannelMemberStatus::Approved->value,
            'role' => ChannelRoles::Member->value,
            'joined_at' => now(),
        ]);

        if ($withMessage) {
            $message = Message::factory()->create([
                'sender_id' => $profile->id,
                'messageable_type' => MessageableType::Channel->value,
                'messageable_id' => $channel->id,
            ]);
            $channel->update(['last_message_id' => $message->id]);
            $channel = $channel->fresh();
        }

        return $channel;
    }
}
