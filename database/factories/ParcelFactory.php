<?php

namespace Database\Factories;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Support\GeometryHelper;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParcelFactory extends Factory
{
    protected $model = Parcel::class;

    /**
     * Coordinate delta in degrees for generating test parcel boundaries.
     * At Jakarta's latitude (~-6.25°), 0.001° ≈ 111 meters.
     */
    private const COORDINATE_DELTA = 0.001;

    public function definition(): array
    {
        $lat = -6.25 + (rand(-100, 100) / 10000);
        $lng = 106.62 + (rand(-100, 100) / 10000);
        $size = self::COORDINATE_DELTA;

        // GeoJSON order: [lng, lat]
        $coordinates = [
            [$lng, $lat],
            [$lng + $size, $lat],
            [$lng + $size, $lat + $size],
            [$lng, $lat + $size],
            [$lng, $lat],
        ];

        $centroid = GeometryHelper::centroidFromCoordinates($coordinates);

        return [
            'owner_name' => fake()->name(),
            'status' => fake()->randomElement(ParcelStatus::values()),
            'price_per_sqm' => fake()->numberBetween(5000000, 15000000),
            'boundary' => GeometryHelper::polygonFromCoordinates([$coordinates]),
            'centroid' => $centroid,
            'area_sqm' => null,
        ];
    }
}
