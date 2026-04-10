<?php

namespace App\Repositories;

use App\Models\Parcel;
use Illuminate\Database\Eloquent\Collection;

class ParcelRepository
{
    public function __construct(
        private Parcel $model
    ) {}

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find(int $id): ?Parcel
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Parcel
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Parcel
    {
        return $this->model->create($data);
    }

    public function update(Parcel $parcel, array $data): bool
    {
        return $parcel->update($data);
    }

    public function delete(Parcel $parcel): bool
    {
        return $parcel->delete();
    }

    public function findByStatus(string $status): Collection
    {
        return $this->model->withStatus($status)->get();
    }

    public function findWithinBoundingBox(
        float $minLng,
        float $minLat,
        float $maxLng,
        float $maxLat
    ): Collection {
        return $this->model::whereRaw(
            'ST_Intersects(boundary, ST_GeomFromText(?))',
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
            ]
        )->get();
    }

    public function findWithinBuffer(
        float $longitude,
        float $latitude,
        float $distanceInMeters
    ): Collection {
        return $this->model::whereRaw(
            'ST_Intersects(boundary, ST_Buffer(ST_GeomFromText(?, 4326), ?))',
            [
                sprintf('POINT(%s %s)', $longitude, $latitude),
                $distanceInMeters,
            ]
        )->get();
    }

    public function findWithinBufferOfParcel(
        int $parcelId,
        float $distanceInMeters
    ): Collection {
        $parcel = $this->findOrFail($parcelId);

        if (! $parcel->boundary) {
            return new Collection;
        }

        return $this->model::whereRaw(
            'ST_Intersects(boundary, ST_Buffer(boundary, ?))',
            [$distanceInMeters]
        )
            ->where('id', '!=', $parcelId)
            ->get();
    }

    public function getAggregateAreaByStatus(): Collection
    {
        return $this->model::selectRaw('status, SUM(area_sqm) as total_area')
            ->groupBy('status')
            ->get();
    }

    public function calculateArea(Parcel $parcel): ?float
    {
        if (! $parcel->boundary) {
            return null;
        }

        $result = $this->model::whereKey($parcel->id)
            ->selectRaw('ST_Area(boundary) as area')
            ->first();

        return $result ? (float) $result->area : null;
    }
}
