<?php

namespace App\DataTransferObjects;

use Illuminate\Database\Eloquent\Collection;

final readonly class MessagePage
{
    public function __construct(
        public Collection $messages,
        public bool $hasMoreBefore,
        public bool $hasMoreAfter,
    ) {
    }
}
