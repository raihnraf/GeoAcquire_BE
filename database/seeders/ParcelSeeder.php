<?php

namespace Database\Seeders;

use App\Models\Parcel;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParcelSeeder extends Seeder
{
    /**
     * Gading Serpong, Tangerang coordinates (approximate center: -6.2527, 106.6188)
     * Creates 15 dummy land parcels in the area.
     */
    public function run(): void
    {
        $parcels = [
            [
                'owner_name' => 'PT Paramount Land',
                'status' => 'target',
                'price_per_sqm' => 12500000,
                'coordinates' => [
                    [-6.2500, 106.6150],
                    [-6.2500, 106.6170],
                    [-6.2510, 106.6170],
                    [-6.2510, 106.6150],
                    [-6.2500, 106.6150],
                ],
            ],
            [
                'owner_name' => 'Budi Santoso',
                'status' => 'negotiating',
                'price_per_sqm' => 11000000,
                'coordinates' => [
                    [-6.2520, 106.6160],
                    [-6.2520, 106.6180],
                    [-6.2530, 106.6180],
                    [-6.2530, 106.6160],
                    [-6.2520, 106.6160],
                ],
            ],
            [
                'owner_name' => 'Siti Rahayu',
                'status' => 'free',
                'price_per_sqm' => 9500000,
                'coordinates' => [
                    [-6.2540, 106.6170],
                    [-6.2540, 106.6190],
                    [-6.2550, 106.6190],
                    [-6.2550, 106.6170],
                    [-6.2540, 106.6170],
                ],
            ],
            [
                'owner_name' => 'Ahmad Wijaya',
                'status' => 'target',
                'price_per_sqm' => 13000000,
                'coordinates' => [
                    [-6.2490, 106.6180],
                    [-6.2490, 106.6200],
                    [-6.2500, 106.6200],
                    [-6.2500, 106.6180],
                    [-6.2490, 106.6180],
                ],
            ],
            [
                'owner_name' => 'Dewi Lestari',
                'status' => 'free',
                'price_per_sqm' => 10000000,
                'coordinates' => [
                    [-6.2560, 106.6140],
                    [-6.2560, 106.6160],
                    [-6.2570, 106.6160],
                    [-6.2570, 106.6140],
                    [-6.2560, 106.6140],
                ],
            ],
            [
                'owner_name' => 'Rudi Hermawan',
                'status' => 'negotiating',
                'price_per_sqm' => 11500000,
                'coordinates' => [
                    [-6.2510, 106.6200],
                    [-6.2510, 106.6220],
                    [-6.2520, 106.6220],
                    [-6.2520, 106.6200],
                    [-6.2510, 106.6200],
                ],
            ],
            [
                'owner_name' => 'Nina Kartika',
                'status' => 'free',
                'price_per_sqm' => 9000000,
                'coordinates' => [
                    [-6.2570, 106.6190],
                    [-6.2570, 106.6210],
                    [-6.2580, 106.6210],
                    [-6.2580, 106.6190],
                    [-6.2570, 106.6190],
                ],
            ],
            [
                'owner_name' => 'Hendra Gunawan',
                'status' => 'target',
                'price_per_sqm' => 14000000,
                'coordinates' => [
                    [-6.2480, 106.6160],
                    [-6.2480, 106.6180],
                    [-6.2490, 106.6180],
                    [-6.2490, 106.6160],
                    [-6.2480, 106.6160],
                ],
            ],
            [
                'owner_name' => 'Maya Sari',
                'status' => 'free',
                'price_per_sqm' => 8500000,
                'coordinates' => [
                    [-6.2550, 106.6210],
                    [-6.2550, 106.6230],
                    [-6.2560, 106.6230],
                    [-6.2560, 106.6210],
                    [-6.2550, 106.6210],
                ],
            ],
            [
                'owner_name' => 'Joko Prasetyo',
                'status' => 'negotiating',
                'price_per_sqm' => 12000000,
                'coordinates' => [
                    [-6.2530, 106.6130],
                    [-6.2530, 106.6150],
                    [-6.2540, 106.6150],
                    [-6.2540, 106.6130],
                    [-6.2530, 106.6130],
                ],
            ],
            [
                'owner_name' => 'Rina Susanti',
                'status' => 'free',
                'price_per_sqm' => 9800000,
                'coordinates' => [
                    [-6.2580, 106.6160],
                    [-6.2580, 106.6180],
                    [-6.2590, 106.6180],
                    [-6.2590, 106.6160],
                    [-6.2580, 106.6160],
                ],
            ],
            [
                'owner_name' => 'Agus Setiawan',
                'status' => 'target',
                'price_per_sqm' => 13500000,
                'coordinates' => [
                    [-6.2470, 106.6190],
                    [-6.2470, 106.6210],
                    [-6.2480, 106.6210],
                    [-6.2480, 106.6190],
                    [-6.2470, 106.6190],
                ],
            ],
            [
                'owner_name' => 'Linda Kusuma',
                'status' => 'free',
                'price_per_sqm' => 8000000,
                'coordinates' => [
                    [-6.2590, 106.6130],
                    [-6.2590, 106.6150],
                    [-6.2600, 106.6150],
                    [-6.2600, 106.6130],
                    [-6.2590, 106.6130],
                ],
            ],
            [
                'owner_name' => 'Tony Susanto',
                'status' => 'negotiating',
                'price_per_sqm' => 11800000,
                'coordinates' => [
                    [-6.2540, 106.6100],
                    [-6.2540, 106.6120],
                    [-6.2550, 106.6120],
                    [-6.2550, 106.6100],
                    [-6.2540, 106.6100],
                ],
            ],
            [
                'owner_name' => 'Fitri Handayani',
                'status' => 'free',
                'price_per_sqm' => 9200000,
                'coordinates' => [
                    [-6.2600, 106.6200],
                    [-6.2600, 106.6220],
                    [-6.2610, 106.6220],
                    [-6.2610, 106.6200],
                    [-6.2600, 106.6200],
                ],
            ],
        ];

        foreach ($parcels as $parcelData) {
            $coordinates = $parcelData['coordinates'];
            unset($parcelData['coordinates']);

            $boundary = $this->createPolygon($coordinates);
            $centroid = $this->calculateCentroid($coordinates);

            Parcel::create(array_merge($parcelData, [
                'boundary' => $boundary,
                'centroid' => $centroid,
            ]));
        }

        $this->command->info('15 parcels seeded in Gading Serpong area');
    }

    private function createPolygon(array $coordinates): Polygon
    {
        $points = array_map(
            fn ($coord) => new Point($coord[0], $coord[1]),
            $coordinates
        );

        return new Polygon([new LineString($points)]);
    }

    private function calculateCentroid(array $coordinates): Point
    {
        $latSum = 0.0;
        $lngSum = 0.0;
        $count = count($coordinates);

        foreach ($coordinates as $coord) {
            $latSum += $coord[0];
            $lngSum += $coord[1];
        }

        return new Point($latSum / $count, $lngSum / $count);
    }
}
