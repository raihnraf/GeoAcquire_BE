<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ParcelCollectionResource extends ResourceCollection
{
    /**
     * Transform the collection into a GeoJSON FeatureCollection.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => ParcelResource::collection($this->collection),
            'metadata' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}
