<?php

namespace App\Repositories;

use App\Models\Parcel;
use Illuminate\Database\Eloquent\Collection;

/**
 * Spatial query repository — only non-trivial database queries live here.
 * Simple CRUD operations use Eloquent directly in the Service layer.
 */
class ParcelRepository
{
    public function __construct(
        private Parcel $model
    ) {}

    public function findByStatus(string $status): Collection
    {
        return $this->model->withStatus($status)->get();
    }

    public function findWithinBuffer(
        float $longitude,
        float $latitude,
        float $distanceInMeters
    ): Collection {
        // ST_Distance_Sphere only works with POINT geometries in geographic SRID
        // Use centroid (POINT) for distance calculation instead of boundary (POLYGON)
        return $this->model::whereRaw(
            "ST_Distance_Sphere(centroid, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326, 'axis-order=long-lat')) <= ?",
            [$longitude, $latitude, $distanceInMeters]
        )->get();
    }

    public function findWithinBufferOfParcel(
        int $parcelId,
        float $distanceInMeters
    ): Collection {
        $parcel = $this->model->findOrFail($parcelId);

        if (! $parcel->boundary) {
            return new Collection;
        }

        // Use centroid for distance calculation - more efficient for parcel-to-parcel queries
        $centroid = $parcel->centroid ?? $parcel->boundary->centroid;

        if (! $centroid) {
            return new Collection;
        }

        // ST_Distance_Sphere returns meters - accurate for geographic coordinates
        // Use axis-order=long-lat to match WKT format from eloquent-spatial
        return $this->model::whereRaw(
            'ST_Distance_Sphere(centroid, ST_GeomFromText(?, 4326, ?)) <= ?',
            [$centroid->toWkt(), 'axis-order=long-lat', $distanceInMeters]
        )
            ->where('id', '!=', $parcelId)
            ->get();
    }

    public function findWithinBoundingBox(
        float $minLng,
        float $minLat,
        float $maxLng,
        float $maxLat
    ): Collection {
        // Specify SRID 4326 and axis-order to match boundary column
        return $this->model::whereRaw(
            'ST_Intersects(boundary, ST_GeomFromText(?, 4326, ?))',
            [
                sprintf(
                    'POLYGON((%s %s, %s %s, %s %s, %s %s, %s %s))',
                    $minLng,
                    $minLat,
                    $maxLng,
                    $minLat,
                    $maxLng,
                    $maxLat,
                    $minLng,
                    $maxLat,
                    $minLng,
                    $minLat
                ),
                'axis-order=long-lat',
            ]
        )->get();
    }

    public function getAggregateAreaByStatus(): Collection
    {
        return $this->model::selectRaw('status, SUM(area_sqm) as total_area')
            ->groupBy('status')
            ->get();
    }
}
