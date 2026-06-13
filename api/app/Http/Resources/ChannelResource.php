<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
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
            'owner' => $this->whenLoaded('owner'),
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'visibility' => $this->visibility,
            'can_join_by_link' => $this->can_join_by_link,
            'confirm_joined' => $this->confirm_joined,
            'messages_count' => $this->messages_count,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
