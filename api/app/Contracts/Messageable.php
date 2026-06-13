<?php

namespace App\Contracts;

use App\Models\Message;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Messageable
{
    public function messages(): MorphMany;

    public function addMessage(Profile $sender, array $data): Message;

    public function canReceiveMessageFrom(Profile $sender): bool;
}
