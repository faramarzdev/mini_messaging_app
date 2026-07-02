<?php

namespace App\Http\Resources;

class ChatCollection extends BaseCollection
{
    public $collects = ChatItemResource::class;
}
