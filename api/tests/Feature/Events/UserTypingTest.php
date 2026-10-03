<?php

namespace Tests\Feature\Events;

use App\Enums\ChannelType;
use App\Events\UserTyping;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class UserTypingTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    #[Test]
    public function it_broadcasts_as_user_typing(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();

        $this->assertSame('user.typing', (new UserTyping($lowerUser->profile, $conversation))->broadcastAs());
    }

    #[Test]
    public function conversation_typing_broadcasts_on_the_private_conversation_channel(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();

        $channels = (new UserTyping($lowerUser->profile, $conversation))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-conversation.'.$conversation->id, $channels[0]->name);
    }

    #[Test]
    public function channel_typing_broadcasts_on_the_private_channel_channel(): void
    {
        [$group, $owner] = $this->createChannel(channelType: ChannelType::Group);

        $channels = (new UserTyping($owner->profile, $group))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-channel.'.$group->id, $channels[0]->name);
    }

    #[Test]
    public function payload_contains_the_typers_name(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();

        $payload = (new UserTyping($lowerUser->profile, $conversation))->broadcastWith();

        $this->assertSame(['name' => $lowerUser->name], $payload);
    }

    #[Test]
    public function payload_falls_back_to_empty_string_when_profileable_is_missing(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();
        $typer = $lowerUser->profile;
        $typer->setRelation('profileable', null); // in memory only

        $payload = (new UserTyping($typer, $conversation))->broadcastWith();

        $this->assertSame(['name' => ''], $payload);
    }

    #[Test]
    public function it_does_not_query_when_the_typers_profileable_is_already_loaded(): void
    {
        [$conversation, $lowerUser] = $this->createConversation();
        $typer = $lowerUser->profile;
        $typer->loadMissing('profileable');

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new UserTyping($typer, $conversation))->broadcastWith();

        $this->assertCount(0, DB::getQueryLog());
    }
}
