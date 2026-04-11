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
        // Create parcel near reference point
        $nearParcel = Parcel::factory()->create([
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

        // Create parcel far from reference point (~50km)
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
        $this->assertEquals($nearParcel->id, $features[0]['id']);
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
}
