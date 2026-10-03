<?php

namespace Tests\Feature\Events;

use App\Enums\MessageableType;
use App\Enums\MessageType;
use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesChannels;
use Tests\Concerns\CreatesConversations;
use Tests\TestCase;

class MessageSentTest extends TestCase
{
    use CreatesChannels, CreatesConversations;

    #[Test]
    public function it_broadcasts_as_message_sent(): void
    {
        [, , , $message] = $this->createConversation(makeMessage: true);

        $this->assertSame('message.sent', (new MessageSent($message))->broadcastAs());
    }

    #[Test]
    public function conversation_message_broadcasts_on_the_private_conversation_channel(): void
    {
        [$conversation, , , $message] = $this->createConversation(makeMessage: true);

        $channels = (new MessageSent($message))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-conversation.'.$conversation->id, $channels[0]->name);
    }

    #[Test]
    public function channel_message_broadcasts_on_the_private_channel_channel(): void
    {
        [$channel, $owner] = $this->createChannel();
        $message = Message::factory()->create([
            'sender_id' => $owner->profile->id,
            'messageable_type' => MessageableType::Channel->value,
            'messageable_id' => $channel->id,
        ]);

        $channels = (new MessageSent($message))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-channel.'.$channel->id, $channels[0]->name);
    }

    #[Test]
    public function payload_contains_message_and_sender_data(): void
    {
        [, $lowerUser, , $message] = $this->createConversation(makeMessage: true);

        $payload = (new MessageSent($message))->broadcastWith();

        $this->assertSame($message->id, $payload['message']['id']);
        $this->assertSame($message->body, $payload['message']['body']);
        $this->assertSame(MessageType::Text, $payload['message']['type']);
        $this->assertSame($message->created_at->toISOString(), $payload['message']['created_at']);

        $this->assertSame($lowerUser->profile->id, $payload['message']['sender']['id']);
        $this->assertSame($lowerUser->name, $payload['message']['sender']['name']);
        $this->assertSame($lowerUser->profile->handle, $payload['message']['sender']['handle']);

        // what actually goes over the wire: the enum must serialize to its value
        $this->assertSame('text', json_decode(json_encode($payload), true)['message']['type']);
    }

    #[Test]
    public function it_does_not_re_query_relations_the_caller_already_loaded(): void
    {
        [, , , $message] = $this->createConversation(makeMessage: true);

        // Arrange: prepare the message exactly like MessageController::store() does
        $message->loadMissing('sender.profileable');

        // Start counting only AFTER the arrangement, so setup queries don't count
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Act: this is the code under test
        (new MessageSent($message))->broadcastWith();

        // Assert: everything was already loaded, so no queries are needed
        $queries = DB::getQueryLog();
        $this->assertCount(
            0,
            $queries,
            'MessageSent re-queried loaded relations: '.json_encode(array_column($queries, 'query'))
        );
    }

    #[Test]
    public function unknown_messageable_type_broadcasts_nowhere(): void
    {
        [, , , $message] = $this->createConversation(makeMessage: true);
        $message->messageable_type = 'something_else'; // in memory only, not saved

        $this->assertSame([], (new MessageSent($message))->broadcastOn());
    }
}
