<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => $this->paginationMeta(),
        ];
    }

    protected function paginationMeta()
    {
        if (! $this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return null;
        }

        return [
            'total' => $this->total(),

            'total_page' => $this->lastPage(),
            'current_page' => $this->currentPage(),
            'next_page' => $this->nextPageUrl(),
            'prev_page' => $this->previousPageUrl(),

            'per_page' => $this->perPage(),

            'from' => $this->firstItem(),
            'to' => $this->lastItem(),

            'has_more' => $this->currentPage() < $this->lastPage(),
        ];
    }
}
