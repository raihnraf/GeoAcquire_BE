<?php

namespace Database\Factories;

use App\Models\Parcel;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParcelFactory extends Factory
{
    protected $model = Parcel::class;

    public function definition(): array
    {
        $lat = -6.25 + (rand(-100, 100) / 10000);
        $lng = 106.62 + (rand(-100, 100) / 10000);
        $size = 0.001;

        $coordinates = [
            [$lat, $lng],
            [$lat, $lng + $size],
            [$lat + $size, $lng + $size],
            [$lat + $size, $lng],
            [$lat, $lng],
        ];

        $points = array_map(
            fn ($c) => new Point($c[0], $c[1]),
            $coordinates
        );

        $centroidLat = array_sum(array_column($coordinates, 0)) / count($coordinates);
        $centroidLng = array_sum(array_column($coordinates, 1)) / count($coordinates);

        return [
            'owner_name' => fake()->name(),
            'status' => fake()->randomElement(['free', 'negotiating', 'target']),
            'price_per_sqm' => fake()->numberBetween(5000000, 15000000),
            'boundary' => new Polygon([new LineString($points)]),
            'centroid' => new Point($centroidLat, $centroidLng),
            'area_sqm' => null,
        ];
    }
}
