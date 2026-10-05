<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    #[Test]
    public function user_can_hide_their_conversations()
    {
        $lowerUser = User::factory()->create();
        $lowerProfile = $lowerUser->profile;

        $user = User::factory()->create();
        $profile = $user->profile;

        $conversationAsLower = Conversation::factory()->create([
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $conversationAsHigher = Conversation::factory()->create([
            'lower_profile_id' => $lowerProfile->id,
            'is_available_for_lower_profile' => true,
            'higher_profile_id' => $profile->id,
            'is_available_for_higher_profile' => true,
        ]);

        Conversation::factory(4)->create();
        $this->assertDatabaseCount(Conversation::class, 6);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsLower->id,
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $this->actingAs($user, 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversationAsLower->id]));
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsLower->id,
            'lower_profile_id' => $profile->id,
            'is_available_for_lower_profile' => false,
            'is_available_for_higher_profile' => true,
        ]);

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsHigher->id,
            'higher_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => true,
        ]);
        $this->actingAs($user, 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversationAsHigher->id]));
        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversationAsHigher->id,
            'higher_profile_id' => $profile->id,
            'is_available_for_lower_profile' => true,
            'is_available_for_higher_profile' => false,
        ]);

    }

    #[Test]
    public function user_cannot_hide_others_conversations()
    {
        [$conversation] = $this->createConversation();

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson(route('conversations.hide', ['conversation' => $conversation->id]));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas(Conversation::class, $conversation->toArray());
    }

    #[Test]
    public function conversation_creation_would_populate_its_last_activity_at()
    {
        Carbon::setTestNow('2026-09-27 12:00:00');

        [$conversation] = $this->createConversation();

        $this->assertDatabaseHas(Conversation::class, [
            'id' => $conversation['id'],
            'last_activity_at' => '2026-09-27 12:00:00',
        ]);
    }

    #[Test]
    public function hiding_a_conversation_does_not_change_last_activity_at(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();
        $original = $conversation->last_activity_at->copy();

        Carbon::setTestNow('2025-01-01 16:00:00');

        $this->actingAs($lowerUser)->postJson(
            route('conversations.hide', $conversation->id)
        )->assertSuccessful();

        $this->assertTrue($conversation->fresh()->last_activity_at->equalTo($original));
    }

    #[Test]
    public function two_users_cannot_make_two_conversations()
    {
        Event::fake([MessageSent::class]);

        $senderUser = User::factory()->create();
        $senderProfile = $senderUser->profile;
        $receiverUser = User::factory()->create();
        $receiverProfile = $receiverUser->profile;

        [$lowerProfileId, $higherProfileId] = Conversation::normalizeProfiles($senderProfile->id, $receiverProfile->id);

        $this->actingAs($senderUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $receiverUser->profile->handle,
                'body' => 'test message',
            ])
            ->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $lowerProfileId,
            'higher_profile_id' => $higherProfileId,
        ]);
        $this->assertDatabaseMissing(Conversation::class, [
            'lower_profile_id' => $higherProfileId,
            'higher_profile_id' => $lowerProfileId,
        ]);
        $this->assertDatabaseCount(Conversation::class, 1);

        // send a message from the other profile
        $this->actingAs($receiverUser, 'sanctum')
            ->postJson(route('message.store'), [
                'receiver_handle' => $senderUser->profile->handle,
                'body' => 'test message',
            ])
            ->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(Conversation::class, [
            'lower_profile_id' => $lowerProfileId,
            'higher_profile_id' => $higherProfileId,
        ]);
        $this->assertDatabaseMissing(Conversation::class, [
            'lower_profile_id' => $higherProfileId,
            'higher_profile_id' => $lowerProfileId,
        ]);
        $this->assertDatabaseCount(Conversation::class, 1);
    }
}
