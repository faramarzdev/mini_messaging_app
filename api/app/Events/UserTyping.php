<?php

namespace App\Events;

use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Profile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Profile $typer,
        public Conversation|Channel $messageable,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->messageable instanceof Conversation) {
            return [
                new PrivateChannel('conversation.'.$this->messageable->id),
            ];
        } elseif ($this->messageable instanceof Channel) {
            return [
                new PrivateChannel('channel.'.$this->messageable->id),
            ];
        }

        return [];

    }

    public function broadcastWith(): array
    {
        return [
            'name' => $this->typer->profileable?->name ?? '',
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }
}
