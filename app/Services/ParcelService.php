<?php

namespace App\Services;

use App\Enums\ParcelStatus;
use App\Exceptions\InvalidGeometryException;
use App\Models\Parcel;
use App\Repositories\ParcelRepository;
use App\Rules\GeoJsonPolygon;
use App\Support\GeometryHelper;
use Illuminate\Database\Eloquent\Collection;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParcelService
{
    public function __construct(
        private ParcelRepository $repository
        )
    {
    }

    public function getAllParcels(): Collection
    {
        return Parcel::all();
    }

    public function getParcelCount(): int
    {
        return $this->repository->getCount();
    }

    public function getParcelCountByStatus(): array
    {
        return $this->repository->getCountByStatus();
    }

    public function getPaginatedParcels(int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Parcel::paginate($perPage);
    }

    public function getParcel(int $id): Parcel
    {
        return Parcel::findOrFail($id);
    }

    public function createParcel(array $data): Parcel
    {
        $geometry = $this->parseGeometryFromGeoJson($data['geometry']);

        $parcelData = [
            'boundary' => $geometry,
            'owner_name' => $data['owner_name'],
            'status' => $data['status'] ?? ParcelStatus::Free->value,
            'price_per_sqm' => $data['price_per_sqm'] ?? null,
        ];

        $parcel = Parcel::create($parcelData);

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

        $parcel->update($updateData);

        if (isset($updateData['boundary'])) {
            $parcel->loadArea();
        }

        return $parcel->fresh();
    }

    public function deleteParcel(Parcel $parcel): bool
    {
        return $parcel->delete();
    }

    public function findParcelsByStatus(string $status): Collection
    {
        return $this->repository->findByStatus($status);
    }

    public function findParcelsByStatuses(array $statuses): Collection
    {
        return $this->repository->findByStatuses($statuses);
    }

    public function findParcelsWithinBuffer(
        float $longitude,
        float $latitude,
        float $distanceInMeters
        ): Collection
    {
        return $this->repository->findWithinBuffer($longitude, $latitude, $distanceInMeters);
    }

    public function findParcelsWithinBufferOfParcel(
        int $parcelId,
        float $distanceInMeters
        ): Collection
    {
        return $this->repository->findWithinBufferOfParcel($parcelId, $distanceInMeters);
    }

    public function findParcelsWithinBoundingBox(
        float $minLng,
        float $minLat,
        float $maxLng,
        float $maxLat
        ): Collection
    {
        return $this->repository->findWithinBoundingBox($minLng, $minLat, $maxLng, $maxLat);
    }

    public function calculateParcelArea(Parcel $parcel): ?float
    {
        return $parcel->calculateArea();
    }

    public function getAggregateAreaByStatus(): Collection
    {
        return $this->repository->getAggregateAreaByStatus();
    }

    /**
     * Get parcels with optional filters (bbox, status).
     * Reused by index() and export() to avoid query duplication.
     *
     * @return Collection<Parcel>
     */
    public function getFilteredParcels(?array $bbox, ?array $statuses, ?int $limit = null): Collection
    {
        if ($bbox !== null) {
            [$minLng, $minLat, $maxLng, $maxLat] = $bbox;
            $parcels = $this->repository->findWithinBoundingBox($minLng, $minLat, $maxLng, $maxLat);

            if ($statuses !== null && count($statuses) > 0) {
                $parcels = $parcels->whereIn('status', $statuses);
            }

            if ($limit !== null) {
                $parcels = $parcels->take($limit);
            }

            return $parcels;
        }

        if ($statuses !== null && count($statuses) > 0) {
            $parcels = $this->repository->findByStatuses($statuses);

            if ($limit !== null) {
                $parcels = $parcels->take($limit);
            }

            return $parcels;
        }

        // No filters: return all parcels
        $parcels = $this->repository->getAll();

        if ($limit !== null) {
            $parcels = $parcels->take($limit);
        }

        return $parcels;
    }

    public function importGeoJsonFeatures(array $geojsonData): array
    {
        $imported = 0;
        $errors = [];

        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            throw new \InvalidArgumentException('Invalid GeoJSON: missing features array');
        }

        foreach ($geojsonData['features'] as $index => $feature) {
            try {
                $this->validateGeoJsonFeature($feature);

                $geometry = $this->parseGeometryFromGeoJson($feature['geometry']);
                $properties = $feature['properties'] ?? [];

                Parcel::create([
                    'boundary' => $geometry,
                    'owner_name' => $properties['owner_name'] ?? 'Unknown',
                    'status' => $properties['status'] ?? ParcelStatus::Free->value,
                    'price_per_sqm' => $properties['price_per_sqm'] ?? null,
                ]);

                $imported++;
            }
            catch (\Exception $e) {
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
            throw InvalidGeometryException::unsupportedType($geometry['type']);
        }

        return GeometryHelper::polygonFromCoordinates($geometry['coordinates']);
    }

    private function validateGeoJsonFeature(array $feature): void
    {
        if (!isset($feature['geometry']) || !is_array($feature['geometry'])) {
            throw InvalidGeometryException::invalidCoordinates('Feature missing geometry');
        }

        // Use the shared GeoJsonPolygon validation logic
        $error = GeoJsonPolygon::validateGeometry($feature['geometry']);

        if ($error !== null) {
            throw InvalidGeometryException::invalidCoordinates($error);
        }
    }
}