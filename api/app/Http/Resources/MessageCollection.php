<?php

namespace App\Http\Resources;

use App\DataTransferObjects\MessagePage;

class MessageCollection extends BaseCollection
{
    public $collects = MessageResource::class;

    protected bool $hasMoreBefore = false;
    protected bool $hasMoreAfter = false;

    public function __construct(MessagePage $page)
    {
        parent::__construct($page->messages);

        $this->hasMoreBefore = $page->hasMoreBefore;
        $this->hasMoreAfter = $page->hasMoreAfter;
    }

    protected function paginationMeta(): array
    {
        return [
            'has_more_before' => $this->hasMoreBefore,
            'has_more_after' => $this->hasMoreAfter,
        ];
    }
}
