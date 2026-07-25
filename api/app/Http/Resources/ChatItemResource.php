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
            'profile' => [
                'handle' => $this->profile->handle,
                'picture_url' => $this->profile->picture_url,
            ],
            'last_message' => $this->last_message,
            'unread_count' => (int) $this->unread_count,
        ];
    }
}
