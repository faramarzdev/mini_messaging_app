<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'profile' => $this->whenLoaded('profile', fn () => new ProfileResource($this->profile)),
            'role' => $this->role,
            'joined_at' => $this->joined_at,
            'status' => $this->status,
        ];
    }
}
