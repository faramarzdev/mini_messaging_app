<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelType;
use App\Events\UserTyping;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTypingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_user_can_trigger_typing_on_conversation()
    {
        Event::fake([UserTyping::class]);

        $sender = User::factory()->create();
        $receiver = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $sender->profile->id,
            'higher_profile_id' => $receiver->profile->id,
        ]);

        $this->actingAs($sender, 'sanctum')
            ->postJson(route('user.typing'), [
                'conversation_id' => $conversation->id,
            ])
            ->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(UserTyping::class);
    }

    #[Test]
    public function user_cannot_trigger_typing_on_others_conversation()
    {
        Event::fake([UserTyping::class]);

        $user = User::factory()->create();

        $conversation = Conversation::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson(route('user.typing'), [
                'conversation_id' => $conversation->id,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(UserTyping::class);
    }

    #[Test]
    public function guest_cannot_trigger_typing_on_conversation()
    {
        Event::fake([UserTyping::class]);

        $conversation = Conversation::factory()->create();

        $this->postJson(route('user.typing'), ['conversation_id' => $conversation->id])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Event::assertNotDispatched(UserTyping::class);
    }

    #[Test]
    public function authenticated_user_can_trigger_typing_on_group()
    {
        Event::fake([UserTyping::class]);

        $member = User::factory()->create();

        $group = Channel::factory()->create([
            'type' => ChannelType::Group->value,
        ]);
        $group->members()->create([
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved->value,
        ]);

        $this->actingAs($member, 'sanctum')
            ->postJson(route('user.typing'), [
                'channel_id' => $group->id,
            ])
            ->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(UserTyping::class);
    }

    #[Test]
    public function not_members_cannot_trigger_typing_on_group()
    {
        Event::fake([UserTyping::class]);

        $user = User::factory()->create();

        $group = Channel::factory()->create([
            'type' => ChannelType::Group->value,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson(route('user.typing'), [
                'channel_id' => $group->id,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(UserTyping::class);
    }

    #[Test]
    public function guest_cannot_trigger_typing_on_group()
    {
        Event::fake([UserTyping::class]);

        $group = Channel::factory()->create([
            'type' => ChannelType::Group->value,
        ]);

        $this->postJson(route('user.typing'), ['channel_id' => $group->id])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Event::assertNotDispatched(UserTyping::class);
    }
}
