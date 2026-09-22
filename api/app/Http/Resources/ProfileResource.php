<?php

namespace App\Http\Resources;

use App\Enums\ProfileableTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->profileable_type === ProfileableTypes::Channel->value
                ? $this->name
                : $this->profileable->name,
            'handle' => $this->handle,

            'featured_picture' => $this->whenLoaded('featuredPicture'),
            'pictures' => $this->whenLoaded('pictures'),

            'profileable_type' => $this->profileable_type,

            'profileable' => $this->whenLoaded('profileable', function () {
                return $this->resolveProfileableResource($this->profileable);
            }),

        ];
    }

    private function resolveProfileableResource($profileable)
    {
        return match ($profileable::class) {
            \App\Models\User::class => new UserResource($profileable),
            \App\Models\Channel::class => new ChannelResource($profileable),
            default => null, // or throw exception
        };
    }
}
