<?php

namespace Tests\Feature;

use App\Enums\ChannelJoinModes;
use App\Enums\ChannelType;
use App\Enums\ChannelVisibility;
use App\Enums\MessageableType;
use App\Enums\ProfileableTypes;
use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    private array $channelToCreate = [
        'name' => 'Test Channel',
        'description' => null,
        'visibility' => ChannelVisibility::Public,
        'type' => ChannelType::Channel,
        'join_mode' => ChannelJoinModes::Open,
    ];

    #[Test]
    public function user_can_create_channel(): void
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
            'profileable_type' => ProfileableTypes::Channel,

            'handle' => 'test_channel',

        ]);
        $this->assertDatabaseCount(Profile::class, 2);
    }

    #[Test]
    public function channel_management_can_remove_the_channel(): void
    {
        [$channel, $owner] = $this->createChannel();
        $this->assertDatabaseCount(Channel::class, 1);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel->id]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted(Channel::class, [
            'id' => $channel->id,
        ]);
    }

    #[Test]
    public function channel_member_cannot_remove_the_channel(): void
    {
        $member = User::factory()->create();
        [$channel] = $this->createChannel(member: $member);

        $response = $this->actingAs($member, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel['id']]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseCount(Channel::class, 1);
    }

    #[Test]
    public function channel_removal_removes_the_messages_and_the_profile(): void
    {
        Event::fake([MessageSent::class]);

        [$channel, $owner] = $this->createChannel();

        $this->assertDatabaseCount(Message::class, 0);

        Message::factory(10)->create([
            'messageable_type' => MessageableType::Channel,
            'messageable_id' => $channel->id,
        ]);

        $this->assertSame(10, $channel->messages()->count());
        $this->assertSame(0, $channel->messages()->onlyTrashed()->count());

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson(route('channel.destroy', ['channel' => $channel->id]));
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSame(0, $channel->messages()->count());
        $this->assertSame(10, $channel->messages()->onlyTrashed()->count());

        $this->assertSoftDeleted(Channel::class, [
            'id' => $channel->id,
        ]);
    }

    #[Test]
    public function channel_creation_would_populate_its_last_activity_at(): void
    {
        Carbon::setTestNow('2026-09-27 12:00:00');

        $channelOwner = User::factory()->create();
        $response = $this->actingAs($channelOwner, 'sanctum')
            ->postJson(route('channel.store'), [
                ...$this->channelToCreate,
                'handle' => 'test_channel',
            ]);
        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('channels', [
            'id' => $response->json('id'),
            'last_activity_at' => '2026-09-27 12:00:00',
        ]);
    }
}
