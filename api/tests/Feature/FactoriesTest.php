<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProfilePicture;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    #[Test]
    public function user_factory_creates_one()
    {
        $this->assertDatabaseCount(User::class, 0);
        User::factory()->create();
        $this->assertDatabaseCount(User::class, 1);
    }

    #[Test]
    public function passing_states_to_channel_factory_works()
    {
        $this->assertDatabaseCount(User::class, 0);
        Channel::factory()->create();
        $this->assertDatabaseCount(User::class, 1);
        $owner = User::factory()->create();
        $this->assertDatabaseCount(User::class, 2);
        Channel::factory()->create([
            'owner_id' => $owner->id,
        ]);
        $this->assertDatabaseCount(User::class, 2);
    }

    #[Test]
    public function passing_states_to_conversation_factory_works()
    {
        $this->assertDatabaseCount(User::class, 0);
        Conversation::factory()->create();
        $this->assertDatabaseCount(User::class, 2);
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->assertDatabaseCount(User::class, 4);
        Conversation::factory()->create([
            'lower_profile_id' => $a->profile->id,
            'higher_profile_id' => $b->profile->id,
        ]);
        $this->assertDatabaseCount(User::class, 4);
    }

    #[Test]
    public function passing_states_to_message_factory_works()
    {
        $this->assertDatabaseCount(User::class, 0);
        Message::factory()->create();
        $this->assertDatabaseCount(User::class, 2);
        $a = User::factory()->create();
        $b = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'lower_profile_id' => $a->profile->id,
            'higher_profile_id' => $b->profile->id,
        ]);
        $this->assertDatabaseCount(User::class, 4);
        Message::factory()->create([
            'sender_id' => $a->profile->id,
            'messageable_id' => $conversation->id,
        ]);
        $this->assertDatabaseCount(User::class, 4);
    }

    #[Test]
    public function passing_states_to_profile_picture_factory_works()
    {
        $this->assertDatabaseCount(User::class, 0);
        ProfilePicture::factory()->create();
        $this->assertDatabaseCount(User::class, 1);
        $a = User::factory()->create();
        $this->assertDatabaseCount(User::class, 2);
        ProfilePicture::factory()->create([
            'profile_id' => $a->profile->id,
        ]);
        $this->assertDatabaseCount(User::class, 2);
    }
}
