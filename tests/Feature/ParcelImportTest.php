<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_valid_geojson(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'owner_name' => 'Imported 1',
                        'status' => 'free',
                        'price_per_sqm' => 10000000,
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6150, -6.2500],
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => [
                        'owner_name' => 'Imported 2',
                        'status' => 'target',
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6200, -6.2600],
                            [106.6220, -6.2600],
                            [106.6220, -6.2610],
                            [106.6200, -6.2610],
                            [106.6200, -6.2600],
                        ]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(200)
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('errors', [])
            ->assertJsonPath('message', 'Import complete. 2 feature(s) imported successfully.');

        $this->assertDatabaseCount('parcels', 2);
        $this->assertDatabaseHas('parcels', ['owner_name' => 'Imported 1']);
        $this->assertDatabaseHas('parcels', ['owner_name' => 'Imported 2']);
    }

    public function test_import_mixed_valid_invalid_features(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'Valid'],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6150, -6.2500],
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => [],
                    'geometry' => [
                        'type' => 'Point', // Invalid: not a Polygon
                        'coordinates' => [106.6, -6.25],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'Also Valid'],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6200, -6.2600],
                            [106.6220, -6.2600],
                            [106.6220, -6.2610],
                            [106.6200, -6.2610],
                            [106.6200, -6.2600],
                        ]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(200)
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('errors', function ($errors) {
                return count($errors) === 1 &&
                       isset($errors[0]['feature_index']) &&
                       $errors[0]['feature_index'] === 1 &&
                       !empty($errors[0]['error']);
            });

        $this->assertDatabaseCount('parcels', 2);
    }

    public function test_import_rejects_invalid_geojson_structure(): void
    {
        $invalidGeojson = [
            'type' => 'InvalidType',
            'features' => [],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $invalidGeojson);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_import_enforces_feature_limit(): void
    {
        $features = [];
        for ($i = 0; $i < 101; $i++) {
            $features[] = [
                'type' => 'Feature',
                'properties' => ['owner_name' => "Feature {$i}"],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [106.6150, -6.2500],
                        [106.6170, -6.2500],
                        [106.6170, -6.2510],
                        [106.6150, -6.2510],
                        [106.6150, -6.2500],
                    ]],
                ],
            ];
        }

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['features'])
            ->assertJsonPath('errors.features.0', 'Cannot import more than 100 features in a single request.');
    }

    public function test_import_handles_empty_features_array(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['features']);
    }

    public function test_import_with_properties(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'owner_name' => 'Test Owner',
                        'status' => 'negotiating',
                        'price_per_sqm' => 15000000,
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6150, -6.2500],
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(200);

        $this->assertDatabaseHas('parcels', [
            'owner_name' => 'Test Owner',
            'status' => 'negotiating',
            'price_per_sqm' => 15000000.00,
        ]);
    }

    public function test_import_validates_geometry_type(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'Test'],
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [[106.6, -6.25], [106.7, -6.3]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        // After partial success pattern: returns 200 with errors array
        $response->assertStatus(200)
            ->assertJsonPath('imported', 0)
            ->assertJsonPath('errors', function ($errors) {
                return count($errors) === 1 &&
                       isset($errors[0]['feature_index']) &&
                       $errors[0]['feature_index'] === 0 &&
                       !empty($errors[0]['error']);
            });

        $this->assertDatabaseCount('parcels', 0);
    }

    public function test_import_continues_on_individual_failures(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'First'],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6150, -6.2500],
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'Invalid Coords'],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [999, -6.25], // Invalid longitude
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => ['owner_name' => 'Last'],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6200, -6.2600],
                            [106.6220, -6.2600],
                            [106.6220, -6.2610],
                            [106.6200, -6.2610],
                            [106.6200, -6.2600],
                        ]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(200)
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('errors', function ($errors) {
                return count($errors) === 1 &&
                       $errors[0]['feature_index'] === 1;
            });

        $this->assertDatabaseHas('parcels', ['owner_name' => 'First']);
        $this->assertDatabaseHas('parcels', ['owner_name' => 'Last']);
        $this->assertDatabaseMissing('parcels', ['owner_name' => 'Invalid Coords']);
    }

    public function test_import_handles_missing_properties_gracefully(): void
    {
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[
                            [106.6150, -6.2500],
                            [106.6170, -6.2500],
                            [106.6170, -6.2510],
                            [106.6150, -6.2510],
                            [106.6150, -6.2500],
                        ]],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/parcels/import', $geojson);

        $response->assertStatus(200);

        $this->assertDatabaseCount('parcels', 1);
        $parcel = Parcel::first();
        $this->assertEquals('Unknown', $parcel->owner_name);
        $this->assertEquals('free', $parcel->status);
        $this->assertNull($parcel->price_per_sqm);
    }
}
