<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->whenLoaded('sender', fn () => $this->sender, $this->sender_id),
            'is_available_on_sender' => $this->is_available_on_sender,
            /*
            'conversation' => $this->whenLoaded('conversation', fn() => $this->conversation, $this->conversation_id),
            'messageable_id' => $this->whenLoaded(''),
            */
            'is_available_on_receiver' => $this->is_available_on_receiver,
            'body' => $this->body,
            'type' => $this->type,
            // 'medias' => $this->, // currently not implemented, todo: implemented
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'reply' => $this->whenLoaded('replyMessage', fn () => $this->replyMessage, null),
        ];
    }
}
