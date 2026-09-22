<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatItemResource extends JsonResource
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
            'type' => $this->type,
            'profile' => new ProfileResource($this->profile),

            'last_message' => $this->lastMessage
                ? new MessageResource($this->lastMessage)
                : null,
            'unread_count' => (int) $this->unread_count,
        ];
    }
}
