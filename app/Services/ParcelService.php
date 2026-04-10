<?php

namespace App\Services;

use App\Models\Parcel;
use App\Repositories\ParcelRepository;
use Illuminate\Database\Eloquent\Collection;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParcelService
{
    public function __construct(
        private ParcelRepository $repository
    ) {}

    public function getAllParcels(): Collection
    {
        return $this->repository->all();
    }

    public function getParcel(int $id): Parcel
    {
        return $this->repository->findOrFail($id);
    }

    public function createParcel(array $data): Parcel
    {
        $geometry = $this->parseGeometryFromGeoJson($data['geometry']);

        $parcelData = [
            'boundary' => $geometry,
            'owner_name' => $data['owner_name'],
            'status' => $data['status'] ?? 'free',
            'price_per_sqm' => $data['price_per_sqm'] ?? null,
        ];

        $parcel = $this->repository->create($parcelData);

        $parcel->loadArea();

        return $parcel->fresh();
    }

    public function updateParcel(Parcel $parcel, array $data): Parcel
    {
        $updateData = [];

        if (isset($data['geometry'])) {
            $updateData['boundary'] = $this->parseGeometryFromGeoJson($data['geometry']);
        }

        if (isset($data['owner_name'])) {
            $updateData['owner_name'] = $data['owner_name'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (array_key_exists('price_per_sqm', $data)) {
            $updateData['price_per_sqm'] = $data['price_per_sqm'];
        }

        $this->repository->update($parcel, $updateData);

        if (isset($updateData['boundary'])) {
            $parcel->loadArea();
        }

        return $parcel->fresh();
    }

    public function deleteParcel(Parcel $parcel): bool
    {
        return $this->repository->delete($parcel);
    }

    public function findParcelsByStatus(string $status): Collection
    {
        return $this->repository->findByStatus($status);
    }

    public function findParcelsWithinBuffer(
        float $longitude,
        float $latitude,
        float $distanceInMeters
    ): Collection {
        return $this->repository->findWithinBuffer($longitude, $latitude, $distanceInMeters);
    }

    public function findParcelsWithinBufferOfParcel(
        int $parcelId,
        float $distanceInMeters
    ): Collection {
        return $this->repository->findWithinBufferOfParcel($parcelId, $distanceInMeters);
    }

    public function findParcelsWithinBoundingBox(
        float $minLng,
        float $minLat,
        float $maxLng,
        float $maxLat
    ): Collection {
        return $this->repository->findWithinBoundingBox($minLng, $minLat, $maxLng, $maxLat);
    }

    public function calculateParcelArea(Parcel $parcel): ?float
    {
        return $this->repository->calculateArea($parcel);
    }

    public function getAggregateAreaByStatus(): Collection
    {
        return $this->repository->getAggregateAreaByStatus();
    }

    public function importGeoJsonFeatures(array $geojsonData): array
    {
        $imported = 0;
        $errors = [];

        if (! isset($geojsonData['features']) || ! is_array($geojsonData['features'])) {
            throw new \InvalidArgumentException('Invalid GeoJSON: missing features array');
        }

        foreach ($geojsonData['features'] as $index => $feature) {
            try {
                $this->validateGeoJsonFeature($feature);

                $geometry = $this->parseGeometryFromGeoJson($feature['geometry']);
                $properties = $feature['properties'] ?? [];

                $this->repository->create([
                    'boundary' => $geometry,
                    'owner_name' => $properties['owner_name'] ?? 'Unknown',
                    'status' => $properties['status'] ?? 'free',
                    'price_per_sqm' => $properties['price_per_sqm'] ?? null,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'feature_index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    private function parseGeometryFromGeoJson(array $geometry): Polygon
    {
        if ($geometry['type'] !== 'Polygon') {
            throw new \InvalidArgumentException('Only Polygon geometry is supported');
        }

        $rings = [];
        foreach ($geometry['coordinates'] as $ringIndex => $coordinates) {
            $points = [];
            foreach ($coordinates as $coord) {
                $points[] = new Point($coord[1], $coord[0]);
            }
            $rings[] = new LineString($points);
        }

        return new Polygon($rings);
    }

    private function validateGeoJsonFeature(array $feature): void
    {
        if (! isset($feature['geometry']) || ! is_array($feature['geometry'])) {
            throw new \InvalidArgumentException('Feature missing geometry');
        }

        if (! isset($feature['geometry']['type']) || ! isset($feature['geometry']['coordinates'])) {
            throw new \InvalidArgumentException('GeoJSON geometry must have type and coordinates');
        }
    }
}
