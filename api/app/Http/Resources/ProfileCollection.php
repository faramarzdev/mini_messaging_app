<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProfileCollection extends ResourceCollection
{
    public $collects = ProfileResource::class;
}
