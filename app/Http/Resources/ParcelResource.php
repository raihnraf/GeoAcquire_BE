<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelResource extends JsonResource
{
    /**
     * Transform the resource into a GeoJSON Feature.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->id,
            'geometry' => $this->boundary ? json_decode($this->boundary->toJson()) : null,
            'properties' => [
                'owner_name' => $this->owner_name,
                'status' => $this->status,
                'price_per_sqm' => $this->price_per_sqm ? (float) $this->price_per_sqm : null,
                'area_sqm' => $this->area_sqm ? (float) $this->area_sqm : null,
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}
