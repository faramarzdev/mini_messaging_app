<?php

namespace App\Events;

use App\Enums\MessageableType;
use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message
    ) {
        $this->message = $message->load('sender');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->message->messageable_type === MessageableType::Channel->value) {
            return [
                new PrivateChannel('channel.'.$this->message->messageable_id),
            ];
        } elseif ($this->message->messageable_type === MessageableType::Conversation->value) {
            return [
                new PrivateChannel('conversation.'.$this->message->messageable_id),
            ];
        }

        return [];
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender; // sender's profile

        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'type' => $this->message->type,
                'sender' => [
                    'id' => $sender->id,
                    'name' => $sender->profileable->name,
                    'handle' => $sender->handle,
                ],
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
