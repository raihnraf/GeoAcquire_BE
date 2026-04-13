<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ParcelCollectionResource extends ResourceCollection
{
    /**
     * The "data" wrapper that should be applied.
     * Frontend expects response.data.data structure
     *
     * @var string|null
     */
    public static $wrap = 'data';

    /**
     * Transform the collection into a GeoJSON FeatureCollection.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $paginator = $this->resource;
        $items = $paginator instanceof LengthAwarePaginator ? $paginator->getCollection() : $this->collection;

        $metadata = [
            'total' => $paginator instanceof LengthAwarePaginator
                ? $paginator->total()
                : $items->count(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $metadata['current_page'] = $paginator->currentPage();
            $metadata['per_page'] = $paginator->perPage();
            $metadata['last_page'] = $paginator->lastPage();
            $metadata['links'] = [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => ParcelResource::collection($items),
            'metadata' => $metadata,
        ];
    }

    /**
     * Get additional data that should be returned at the root level.
     */
    public function with(Request $request): array
    {
        return [];
    }
}
