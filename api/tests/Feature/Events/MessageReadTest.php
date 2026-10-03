<?php

namespace Tests\Feature\Events;

use App\Enums\ChannelType;
use App\Enums\MessageableType;
use App\Events\MessageRead;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class MessageReadTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    #[Test]
    public function it_broadcasts_as_message_read(): void
    {
        [, , $higherUser, $message] = $this->createConversation(makeMessage: true);

        $event = new MessageRead($message, $higherUser->profile);

        $this->assertSame('message.read', $event->broadcastAs());
    }

    #[Test]
    public function conversation_message_broadcasts_on_the_private_conversation_channel(): void
    {
        [$conversation, , $higherUser, $message] = $this->createConversation(makeMessage: true);

        $channels = (new MessageRead($message, $higherUser->profile))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-conversation.'.$conversation->id, $channels[0]->name);
    }

    #[Test]
    public function group_message_broadcasts_on_the_private_channel_channel(): void
    {
        [$group, $owner] = $this->createChannel(channelType: ChannelType::Group);
        $message = Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $group->id,
        ]);

        $channels = (new MessageRead($message, $owner->profile))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-channel.'.$group->id, $channels[0]->name);
    }

    #[Test]
    public function broadcast_type_channel_message_broadcasts_nowhere(): void
    {
        [$channel, $owner] = $this->createChannel(); // default type: channel
        $message = Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);

        $this->assertSame([], (new MessageRead($message, $owner->profile))->broadcastOn());
    }

    #[Test]
    public function payload_contains_message_id_and_reader_id(): void
    {
        // the sender is the lower user, so the reader is a different person
        [, $lowerUser, $higherUser, $message] = $this->createConversation(makeMessage: true);

        $payload = (new MessageRead($message, $higherUser->profile))->broadcastWith();

        $this->assertSame($message->id, $payload['message_id']);
        $this->assertSame($higherUser->profile->id, $payload['reader_id']);
        $this->assertNotSame($lowerUser->profile->id, $payload['reader_id']);
    }

    #[Test]
    public function it_does_not_re_query_a_messageable_the_caller_already_loaded(): void
    {
        [, , $higherUser, $message] = $this->createConversation(makeMessage: true);
        $message->loadMissing('messageable');

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new MessageRead($message, $higherUser->profile))->broadcastOn();

        $this->assertCount(0, DB::getQueryLog());
    }
}
