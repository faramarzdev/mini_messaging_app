<?php

namespace Tests\Feature;

use App\Enums\ChannelMemberStatus;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function conversation_participant_can_subscribe_to_their_conversation_channel()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $userA->profile->id,
            'higher_profile_id' => $userB->profile->id,
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.'.$conversation->id,
                'socket_id' => '123456.654321', // fake socket ID, Reverb doesn't validate in tests
            ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function non_participant_cannot_subscribe_to_a_conversation_channel()
    {
        $impersonator = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $response = $this->actingAs($impersonator, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.'.$conversation->id,
                'socket_id' => '123456.654321', // fake socket ID
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    #[Test]
    public function approved_channel_member_can_subscribe_to_channel_broadcast()
    {
        $member = User::factory()->create();
        $channel = Channel::factory()->create();
        ChannelMember::factory()->create([
            'channel_id' => $channel->id,
            'profile_id' => $member->profile?->id,
            'status' => ChannelMemberStatus::Approved->value,
        ]);

        $response = $this->actingAs($member, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-channel.'.$channel->id,
                'socket_id' => '123456.654321', // fake socket ID
            ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    #[Test]
    public function non_member_cannot_subscribe_to_channel_broadcast()
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-channel.'.$channel->id,
                'socket_id' => '123456.654321', // fake socket ID
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
