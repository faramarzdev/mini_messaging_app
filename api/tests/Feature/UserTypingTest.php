<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Enums\ChannelType;
use App\Events\UserTyping;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class UserTypingTest extends TestCase
{
    use CreatesConversations;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([UserTyping::class]);
    }

    #[Test]
    public function authenticated_user_can_trigger_typing_on_conversation()
    {

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

        $conversation = Conversation::factory()->create();

        $this->postJson(route('user.typing'), ['conversation_id' => $conversation->id])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Event::assertNotDispatched(UserTyping::class);
    }

    #[Test]
    public function authenticated_user_can_trigger_typing_on_group()
    {

        $member = User::factory()->create();

        $group = Channel::factory()->create([
            'type' => ChannelType::Group,
        ]);
        $group->members()->create([
            'profile_id' => $member->profile->id,
            'status' => ChannelMemberStatus::Approved,
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

        $user = User::factory()->create();

        $group = Channel::factory()->create([
            'type' => ChannelType::Group,
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

        $group = Channel::factory()->create([
            'type' => ChannelType::Group,
        ]);

        $this->postJson(route('user.typing'), ['channel_id' => $group->id])
            ->assertStatus(Response::HTTP_UNAUTHORIZED);

        Event::assertNotDispatched(UserTyping::class);
    }

    #[Test]
    public function typing_event_receives_a_typer_with_profileable_already_loaded(): void
    {
        Event::fake([UserTyping::class]);
        [$conversation, $lowerUser] = $this->createConversation();
        $this->actingAs($lowerUser, 'sanctum')
            ->postJson(route('user.typing'), [
                'conversation_id' => $conversation->id,
            ])
            ->assertStatus(Response::HTTP_OK);

        Event::assertDispatched(UserTyping::class, function (UserTyping $event) {
            return $event->typer->relationLoaded('profileable');
        });
    }
}
