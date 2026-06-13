<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ChannelCollection extends ResourceCollection
{
    public $collects = ChannelResource::class;
}
