<?php

namespace App\Models\Concerns;

use App\Models\Message;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMessages
{
    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable')
            ->orderByDesc('created_at');
    }

    public function addMessage(Profile $sender, array $validatedData): Message
    {
        // todo: handle media attachments
        return $this->messages()->create([
            'sender_id' => $sender->id,
            'body' => $validatedData['body'],
            'type' => $validatedData['type'],
            'reply_id' => $validatedData['reply_id'],
        ]);
    }
}
