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
        $profile = $request->currentProfile();
        return [
            'id' => $this->id,
            'sender' => $this->whenLoaded('sender', fn() => $this->sender, $this->sender_id),
            'body' => $this->body,
            'type' => $this->type,
            // 'medias' => $this->, // currently not implemented, todo: implement it
            'is_read' => $this->isRead($profile->id),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'reply' => $this->whenLoaded('replyMessage', fn() => $this->replyMessage, null),
        ];
    }

    protected function isRead(?int $profileId): bool|null
    {
        // return null if message is not send by current profile
        if ($this->sender_id != $profileId) {
            return null;
        }
        return (bool) $this->is_read;
    }

}
