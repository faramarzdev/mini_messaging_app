<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $toReturn = [
            'id' => $this->id,
            'name' => $this->name,
            'profile' => $this->whenLoaded('profile', fn () => [
                'id' => $this->profile->id,
                'handle' => $this->profile->handle,
            ]),
        ];
        if ($request->user()?->id === $this->id || $request->user()?->role === 'admin') {
            $toReturn['email'] = $this->email;
        }

        return $toReturn;
    }
}
