<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcelAggregateResource extends JsonResource
{
    /**
     * Transform the aggregate data into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource['status'] ?? 'unknown',
            'total_area_sqm' => (float) ($this->resource['total_area'] ?? 0),
            'total_area_hectares' => (float) (($this->resource['total_area'] ?? 0) / 10000),
        ];
    }
}
