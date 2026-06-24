<?php

namespace App\Events;

use App\Enums\ChannelType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message,
        public Profile $readerProfile,
    ) {
        $this->message->loadMissing('messageable');
    }

    public function broadcastOn(): array
    {
        if ($this->message->messageable instanceof Conversation) {
            return [
                new PrivateChannel('conversation.'.$this->message->messageable->id),
            ];
        } elseif (
            $this->message->messageable instanceof \App\Models\Channel &&
            $this->message->messageable->type !== ChannelType::Channel->value
        ) {
            return [
                new PrivateChannel('channel.'.$this->message->messageable->id),
            ];
        }

        return [];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'reader_id' => $this->readerProfile->id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }
}
