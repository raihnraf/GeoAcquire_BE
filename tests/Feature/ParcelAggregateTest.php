<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Tests\TestCase;

class ParcelAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcel_buffer_finds_nearby_parcels(): void
    {
        // Create reference parcel
        $referenceParcel = Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2500, 106.6150),
                    new Point(-6.2500, 106.6170),
                    new Point(-6.2510, 106.6170),
                    new Point(-6.2510, 106.6150),
                    new Point(-6.2500, 106.6150),
                ]),
            ]),
        ]);

        // Create nearby parcel (~100m away)
        $nearbyParcel = Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2515, 106.6190),
                    new Point(-6.2515, 106.6210),
                    new Point(-6.2525, 106.6210),
                    new Point(-6.2525, 106.6190),
                    new Point(-6.2515, 106.6190),
                ]),
            ]),
        ]);

        // Create far parcel (~1km away)
        Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2600, 106.6300),
                    new Point(-6.2600, 106.6320),
                    new Point(-6.2620, 106.6320),
                    new Point(-6.2620, 106.6300),
                    new Point(-6.2600, 106.6300),
                ]),
            ]),
        ]);

        $response = $this->getJson("/api/v1/parcels/{$referenceParcel->id}/buffer?distance=500");

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection');

        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals($nearbyParcel->id, $features[0]['id']);
    }

    public function test_parcel_buffer_excludes_reference_parcel(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer?distance=1000");

        $response->assertStatus(200);

        $features = $response->json('features');
        $this->assertNotEmpty($features);

        // Reference parcel should not be in results
        $ids = array_column($features, 'id');
        $this->assertNotContains($parcel->id, $ids);
    }

    public function test_parcel_buffer_returns_404_for_nonexistent_parcel(): void
    {
        $response = $this->getJson('/api/v1/parcels/999999/buffer?distance=500');

        $response->assertStatus(404);
    }

    public function test_parcel_buffer_validates_distance(): void
    {
        $parcel = Parcel::factory()->create();

        // Test distance too small
        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer?distance=0");
        $response->assertStatus(422);

        // Test distance too large
        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer?distance=50000");
        $response->assertStatus(422);

        // Test default distance (500)
        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer");
        $response->assertStatus(200);
    }

    public function test_aggregate_area_by_status(): void
    {
        // Create parcels with different statuses
        Parcel::factory()->create([
            'status' => 'free',
            'area_sqm' => 10000,
        ]);

        Parcel::factory()->create([
            'status' => 'free',
            'area_sqm' => 5000,
        ]);

        Parcel::factory()->create([
            'status' => 'target',
            'area_sqm' => 15000,
        ]);

        $response = $this->getJson('/api/v1/parcels/aggregate/area?by=status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'status',
                        'total_area_sqm',
                        'total_area_hectares',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data); // free, negotiating, target

        // Find free status aggregate
        $freeAggregate = collect($data)->firstWhere('status', 'free');
        $this->assertNotNull($freeAggregate);
        $this->assertEquals(15000.0, $freeAggregate['total_area_sqm']);
        $this->assertEquals(1.5, $freeAggregate['total_area_hectares']);

        // Find target status aggregate
        $targetAggregate = collect($data)->firstWhere('status', 'target');
        $this->assertNotNull($targetAggregate);
        $this->assertEquals(15000.0, $targetAggregate['total_area_sqm']);

        // Negotiating should have 0 area (no parcels)
        $negotiatingAggregate = collect($data)->firstWhere('status', 'negotiating');
        $this->assertNotNull($negotiatingAggregate);
        $this->assertEquals(0.0, $negotiatingAggregate['total_area_sqm']);
    }

    public function test_aggregate_area_includes_all_statuses(): void
    {
        // Only create free status parcels
        Parcel::factory()->count(2)->create(['status' => 'free']);

        $response = $this->getJson('/api/v1/parcels/aggregate/area?by=status');

        $data = $response->json('data');

        // All three statuses should be present
        $statuses = array_column($data, 'status');
        $this->assertContains('free', $statuses);
        $this->assertContains('negotiating', $statuses);
        $this->assertContains('target', $statuses);
    }

    public function test_aggregate_area_rejects_invalid_by_parameter(): void
    {
        $response = $this->getJson('/api/v1/parcels/aggregate/area?by=invalid');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid aggregation type. Supported: status');
    }

    public function test_aggregate_area_defaults_to_status(): void
    {
        Parcel::factory()->create(['status' => 'free']);

        // Test without 'by' parameter
        $response = $this->getJson('/api/v1/parcels/aggregate/area');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['status', 'total_area_sqm', 'total_area_hectares'],
                ],
            ]);
    }

    public function test_parcel_buffer_with_custom_distance(): void
    {
        $parcel = Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2500, 106.6150),
                    new Point(-6.2500, 106.6170),
                    new Point(-6.2510, 106.6170),
                    new Point(-6.2510, 106.6150),
                    new Point(-6.2500, 106.6150),
                ]),
            ]),
        ]);

        // Create nearby parcel
        $nearbyParcel = Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2515, 106.6190),
                    new Point(-6.2515, 106.6210),
                    new Point(-6.2525, 106.6210),
                    new Point(-6.2525, 106.6190),
                    new Point(-6.2515, 106.6190),
                ]),
            ]),
        ]);

        // Test with 100m distance (should not find nearby parcel)
        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer?distance=100");
        $features = $response->json('features');
        $this->assertCount(0, $features);

        // Test with 1000m distance (should find nearby parcel)
        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/buffer?distance=1000");
        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals($nearbyParcel->id, $features[0]['id']);
    }
}
