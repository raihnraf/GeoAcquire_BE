<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Tests\TestCase;

class SpatialQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounding_box_filter_returns_parcels_within_bounds(): void
    {
        // Create parcel within bbox
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

        // Create parcel outside bbox
        Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.5000, 107.0000),
                    new Point(-6.5000, 107.0100),
                    new Point(-6.5100, 107.0100),
                    new Point(-6.5100, 107.0000),
                    new Point(-6.5000, 107.0000),
                ]),
            ]),
        ]);

        $response = $this->getJson('/api/v1/parcels?bbox=106.600,-6.300,106.650,-6.200');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection');

        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals($parcel->id, $features[0]['id']);
    }

    public function test_bounding_box_validation_rejects_invalid_format(): void
    {
        $response = $this->getJson('/api/v1/parcels?bbox=invalid');

        $response->assertStatus(422);
    }

    public function test_status_filter_returns_matching_parcels(): void
    {
        Parcel::factory()->create(['status' => 'free']);
        Parcel::factory()->create(['status' => 'target']);
        Parcel::factory()->create(['status' => 'negotiating']);

        $response = $this->getJson('/api/v1/parcels?status=target');

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection');

        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals('target', $features[0]['properties']['status']);
    }

    public function test_status_filter_invalid_value_rejected(): void
    {
        $response = $this->getJson('/api/v1/parcels?status=invalid');

        $response->assertStatus(422);
    }

    public function test_buffer_analysis_finds_nearby_parcels(): void
    {
        // Create parcel near reference point (within buffer)
        $parcelWithinBuffer = Parcel::factory()->create([
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

        // Create parcel far from reference point (~50km, outside buffer)
        Parcel::factory()->create([
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.5000, 107.0000),
                    new Point(-6.5000, 107.0100),
                    new Point(-6.5100, 107.0100),
                    new Point(-6.5100, 107.0000),
                    new Point(-6.5000, 107.0000),
                ]),
            ]),
        ]);

        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 500, // 500 meters
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'FeatureCollection');

        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals($parcelWithinBuffer->id, $features[0]['id']);
    }

    public function test_buffer_analysis_validates_distance_limit(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 50000, // Exceeds 10000 meter limit
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['distance']);
    }

    public function test_combined_bbox_and_status_filter(): void
    {
        // Create matching parcel (within bbox AND target status)
        $matchingParcel = Parcel::factory()->create([
            'status' => 'target',
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

        // Create parcel within bbox but wrong status
        Parcel::factory()->create([
            'status' => 'free',
            'boundary' => new Polygon([
                new LineString([
                    new Point(-6.2515, 106.6155),
                    new Point(-6.2515, 106.6165),
                    new Point(-6.2525, 106.6165),
                    new Point(-6.2525, 106.6155),
                    new Point(-6.2515, 106.6155),
                ]),
            ]),
        ]);

        $response = $this->getJson('/api/v1/parcels?bbox=106.600,-6.300,106.650,-6.200&status=target');

        $response->assertStatus(200);

        $features = $response->json('features');
        $this->assertCount(1, $features);
        $this->assertEquals($matchingParcel->id, $features[0]['id']);
        $this->assertEquals('target', $features[0]['properties']['status']);
    }

    public function test_buffer_analysis_requires_coordinates(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'distance' => 500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lng', 'lat']);
    }

    public function test_buffer_analysis_validates_coordinate_ranges(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 200, // Invalid: > 180
            'lat' => -6.25,
            'distance' => 500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lng']);
    }

    public function test_bounding_box_rejects_invalid_longitude(): void
    {
        $response = $this->getJson('/api/v1/parcels?bbox=999,-6,1000,-5');

        $response->assertStatus(422)
            ->assertJsonPath('errors.bbox.0', 'Longitude values must be between -180 and 180');
    }

    public function test_bounding_box_rejects_invalid_latitude(): void
    {
        $response = $this->getJson('/api/v1/parcels?bbox=106,999,107,-5');

        $response->assertStatus(422)
            ->assertJsonPath('errors.bbox.0', 'Latitude values must be between -90 and 90');
    }

    public function test_bounding_box_rejects_min_lng_greater_than_max_lng(): void
    {
        $response = $this->getJson('/api/v1/parcels?bbox=107,-6,106,-5');

        $response->assertStatus(422)
            ->assertJsonPath('errors.bbox.0', 'minLng must be less than maxLng');
    }

    public function test_bounding_box_rejects_min_lat_greater_than_max_lat(): void
    {
        $response = $this->getJson('/api/v1/parcels?bbox=106,-5,107,-6');

        $response->assertStatus(422)
            ->assertJsonPath('errors.bbox.0', 'minLat must be less than maxLat');
    }

    public function test_bounding_box_accepts_valid_edge_case_coordinates(): void
    {
        // Seed some test data in valid range
        $this->seed(\Database\Seeders\ParcelSeeder::class);

        // Test with edge case valid coordinates (-180, -90, 180, 90)
        $response = $this->getJson('/api/v1/parcels?bbox=-180,-90,180,90');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'type',
                'features' => []
            ]);
    }

    /**
     * M4: Null geometry handling - buffer query handles parcel without centroid.
     */
    public function test_buffer_analysis_handles_parcel_without_centroid(): void
    {
        // Create a parcel with boundary but no centroid (edge case)
        $parcelWithoutCentroid = Parcel::factory()->create([
            'centroid' => null,
        ]);

        // Create a parcel with valid centroid
        $validParcel = Parcel::factory()->create([
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

        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 500,
        ]);

        $response->assertStatus(200);

        $features = $response->json('features');
        $this->assertGreaterThan(0, count($features));

        // Should include the valid parcel but not the one without centroid
        $featureIds = array_column($features, 'id');
        $this->assertContains($validParcel->id, $featureIds);
        $this->assertNotContains($parcelWithoutCentroid->id, $featureIds);
    }

    /**
     * M4: Null geometry handling - parcel with null boundary doesn't crash centroid calculation.
     */
    public function test_parcel_creation_with_null_boundary(): void
    {
        // This test verifies that creating a parcel with null boundary
        // doesn't cause issues with the model's centroid calculation logic
        $parcel = Parcel::factory()->create([
            'boundary' => null,
            'centroid' => null,
            'area_sqm' => null,
        ]);

        $this->assertDatabaseHas('parcels', ['id' => $parcel->id]);
        $this->assertNull($parcel->boundary);
        $this->assertNull($parcel->centroid);
        $this->assertNull($parcel->area_sqm);
    }

    // ===== M6: Buffer Boundary Value Tests =====

    /**
     * M6: Test that zero distance buffer is rejected.
     * Buffer distance must be at least 1 meter.
     */
    public function test_buffer_analysis_rejects_zero_distance(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 0,  // Zero distance - should be rejected
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['distance']);
    }

    /**
     * M6: Test that negative distance buffer is rejected.
     * Buffer distance must be a positive integer.
     */
    public function test_buffer_analysis_rejects_negative_distance(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => -100,  // Negative distance - should be rejected
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['distance']);
    }

    /**
     * M6: Test boundary case - minimum valid distance (1 meter).
     */
    public function test_buffer_analysis_accepts_minimum_distance_of_1_meter(): void
    {
        // Create a parcel at the reference point
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

        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 1,  // Minimum valid distance
        ]);

        $response->assertStatus(200);

        // With 1 meter buffer from the parcel's centroid,
        // the parcel itself should be included
        $features = $response->json('features');
        $this->assertGreaterThan(0, count($features));
    }

    /**
     * M6: Test boundary case - maximum valid distance (10000 meters).
     */
    public function test_buffer_analysis_accepts_maximum_distance_of_10000_meters(): void
    {
        $response = $this->postJson('/api/v1/analysis/buffer', [
            'lng' => 106.616,
            'lat' => -6.2505,
            'distance' => 10000,  // Maximum valid distance
        ]);

        $response->assertStatus(200);
    }
}
