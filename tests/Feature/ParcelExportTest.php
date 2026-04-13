<?php

namespace Tests\Feature;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Tests\TestCase;

class ParcelExportTest extends TestCase
{
    use RefreshDatabase;

    private Polygon $testGeometry;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a simple rectangular polygon for testing
        $coordinates = [
            [106.8, -6.2],
            [106.9, -6.2],
            [106.9, -6.1],
            [106.8, -6.1],
            [106.8, -6.2],
        ];

        $this->testGeometry = Polygon::fromCoordinates([$coordinates]);
    }

    /** @test */
    public function it_exports_all_parcels_as_geojson_feature_collection()
    {
        Parcel::factory()->count(3)->create(['boundary' => $this->testGeometry]);

        $response = $this->getJson('/api/v1/parcels/export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'type',
            'features' => [
                '*' => [
                    'type',
                    'id',
                    'geometry',
                    'properties',
                ],
            ],
        ]);

        $data = $response->json();

        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(3, $data['features']);
        $this->assertEquals('Feature', $data['features'][0]['type']);
        $this->assertArrayHasKey('geometry', $data['features'][0]);
        $this->assertArrayHasKey('properties', $data['features'][0]);
    }

    /** @test */
    public function it_exports_empty_feature_collection_when_no_parcels_exist()
    {
        $response = $this->getJson('/api/v1/parcels/export');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertEmpty($data['features']);
    }

    /** @test */
    public function it_filters_by_status()
    {
        Parcel::factory()->create([
            'status' => ParcelStatus::Free->value,
            'boundary' => $this->testGeometry,
        ]);
        Parcel::factory()->create([
            'status' => ParcelStatus::Negotiating->value,
            'boundary' => $this->testGeometry,
        ]);

        $response = $this->getJson('/api/v1/parcels/export?status=free');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertCount(1, $data['features']);
        $this->assertEquals('free', $data['features'][0]['properties']['status']);
    }

    /** @test */
    public function it_filters_by_bounding_box()
    {
        // Create parcel within bbox
        Parcel::factory()->create([
            'centroid' => new Point(-6.15, 106.85),
            'boundary' => Polygon::fromCoordinates([
                [[106.8, -6.2], [106.9, -6.2], [106.9, -6.1], [106.8, -6.1], [106.8, -6.2]],
            ]),
        ]);

        // Create parcel outside bbox
        Parcel::factory()->create([
            'centroid' => new Point(-6.0, 107.0),
            'boundary' => Polygon::fromCoordinates([
                [[107.0, -6.1], [107.1, -6.1], [107.1, -6.0], [107.0, -6.0], [107.0, -6.1]],
            ]),
        ]);

        $response = $this->getJson('/api/v1/parcels/export?bbox=106.8,-6.2,106.9,-6.1');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertCount(1, $data['features']);
    }

    /** @test */
    public function it_filters_by_both_bbox_and_status()
    {
        Parcel::factory()->create([
            'status' => ParcelStatus::Free->value,
            'centroid' => new Point(-6.15, 106.85),
            'boundary' => Polygon::fromCoordinates([
                [[106.8, -6.2], [106.9, -6.2], [106.9, -6.1], [106.8, -6.1], [106.8, -6.2]],
            ]),
        ]);

        Parcel::factory()->create([
            'status' => ParcelStatus::Negotiating->value,
            'centroid' => new Point(-6.15, 106.85),
            'boundary' => Polygon::fromCoordinates([
                [[106.8, -6.2], [106.9, -6.2], [106.9, -6.1], [106.8, -6.1], [106.8, -6.2]],
            ]),
        ]);

        $response = $this->getJson('/api/v1/parcels/export?bbox=106.8,-6.2,106.9,-6.1&status=free');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertCount(1, $data['features']);
        $this->assertEquals('free', $data['features'][0]['properties']['status']);
    }

    /** @test */
    public function it_respects_limit_parameter()
    {
        Parcel::factory()->count(10)->create(['boundary' => $this->testGeometry]);

        $response = $this->getJson('/api/v1/parcels/export?limit=5');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertCount(5, $data['features']);
    }

    /** @test */
    public function it_validates_limit_parameter_max_value()
    {
        Parcel::factory()->count(10)->create(['boundary' => $this->testGeometry]);

        $response = $this->getJson('/api/v1/parcels/export?limit=2000');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    /** @test */
    public function it_validates_limit_parameter_min_value()
    {
        $response = $this->getJson('/api/v1/parcels/export?limit=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['limit']);
    }

    /** @test */
    public function it_validates_invalid_bbox_format()
    {
        $response = $this->getJson('/api/v1/parcels/export?bbox=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bbox']);
    }

    /** @test */
    public function it_validates_bbox_longitude_range()
    {
        $response = $this->getJson('/api/v1/parcels/export?bbox=200,-6.2,210,-6.1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bbox']);
    }

    /** @test */
    public function it_validates_bbox_latitude_range()
    {
        $response = $this->getJson('/api/v1/parcels/export?bbox=106.8,-100,106.9,-90');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bbox']);
    }

    /** @test */
    public function it_validates_bbox_min_max_ordering()
    {
        $response = $this->getJson('/api/v1/parcels/export?bbox=106.9,-6.2,106.8,-6.1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bbox']);
    }

    /** @test */
    public function it_validates_invalid_status_enum()
    {
        $response = $this->getJson('/api/v1/parcels/export?status=invalid');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function it_returns_standard_geojson_format()
    {
        Parcel::factory()->create([
            'owner_name' => 'Test Owner',
            'status' => ParcelStatus::Free->value,
            'price_per_sqm' => 1000.50,
            'area_sqm' => 5000.00,
            'boundary' => $this->testGeometry,
        ]);

        $response = $this->getJson('/api/v1/parcels/export');

        $response->assertStatus(200);

        $data = $response->json();

        // Verify standard GeoJSON structure
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('features', $data);

        $feature = $data['features'][0];

        // Verify Feature structure
        $this->assertEquals('Feature', $feature['type']);
        $this->assertIsInt($feature['id']);

        // Verify Geometry structure
        $this->assertArrayHasKey('type', $feature['geometry']);
        $this->assertArrayHasKey('coordinates', $feature['geometry']);

        // Verify Properties
        $this->assertEquals('Test Owner', $feature['properties']['owner_name']);
        $this->assertEquals('free', $feature['properties']['status']);
        $this->assertEquals(1000.50, $feature['properties']['price_per_sqm']);
        $this->assertEquals(5000.00, $feature['properties']['area_sqm']);
        $this->assertArrayHasKey('created_at', $feature['properties']);
        $this->assertArrayHasKey('updated_at', $feature['properties']);

        // Ensure no metadata wrapper (pure GeoJSON)
        $this->assertArrayNotHasKey('metadata', $data);
    }

    /** @test */
    public function it_excludes_parcels_with_null_boundary()
    {
        Parcel::factory()->create(['boundary' => $this->testGeometry]);
        Parcel::factory()->create(['boundary' => null]);

        $response = $this->getJson('/api/v1/parcels/export');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertCount(1, $data['features']);
    }
}
