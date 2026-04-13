<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use Tests\TestCase;

class AreaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_calculate_parcel_area(): void
    {
        // Create a parcel with a known square polygon
        // Using coordinates near Jakarta where the projection is reasonable.
        // At latitude -6.25°:
        //   - 1 degree longitude ≈ 111 km * cos(6.25°) ≈ 110.4 km
        //   - 1 degree latitude ≈ 111 km
        // For size = 0.001 degrees:
        //   - width ≈ 110.4 meters
        //   - height ≈ 111 meters
        //   - expected area ≈ 12,254 sqm
        $size = 0.001;
        $baseLat = -6.2500;
        $baseLng = 106.6200;

        $coordinates = [
            new Point($baseLat, $baseLng),
            new Point($baseLat, $baseLng + $size),
            new Point($baseLat + $size, $baseLng + $size),
            new Point($baseLat + $size, $baseLng),
            new Point($baseLat, $baseLng),
        ];

        $parcel = Parcel::factory()->create([
            'boundary' => new Polygon([new LineString($coordinates)]),
        ]);

        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/area");

        $response->assertStatus(200)
            ->assertJsonPath('parcel_id', $parcel->id)
            ->assertJsonStructure([
                'parcel_id',
                'area_sqm',
                'area_hectares',
            ]);

        $areaSqm = $response->json('area_sqm');

        // Calculate expected area based on coordinate differences
        // Width: ~110.4m (longitude at this latitude)
        // Height: ~111m (latitude)
        // Expected: ~12,254 sqm
        $expectedAreaSqm = 110.4 * 111.0;

        // Use 20% tolerance to account for SRID 4326 projection differences
        // and MySQL ST_Area calculation variations
        $this->assertEqualsWithDelta(
            $expectedAreaSqm,
            $areaSqm,
            $expectedAreaSqm * 0.20,
            sprintf(
                'Area should be approximately %s sqm (±20%%) for a %.1fm x %.1fm parcel',
                number_format($expectedAreaSqm),
                110.4,
                111.0
            )
        );
    }

    public function test_area_hectares_is_area_sqm_divided_by_10000(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/area");

        $areaSqm = $response->json('area_sqm');
        $areaHectares = $response->json('area_hectares');

        $this->assertEqualsWithDelta($areaSqm / 10000, $areaHectares, 0.001);
    }

    public function test_area_returns_404_for_nonexistent_parcel(): void
    {
        $response = $this->getJson('/api/v1/parcels/99999/area');

        $response->assertStatus(404);
    }

    /**
     * M4: Null geometry handling - area calculation returns null for parcel without boundary.
     */
    public function test_area_returns_null_for_parcel_without_boundary(): void
    {
        // Create a parcel without a boundary (null geometry)
        $parcel = Parcel::factory()->create([
            'boundary' => null,
            'centroid' => null,
            'area_sqm' => null,
        ]);

        $response = $this->getJson("/api/v1/parcels/{$parcel->id}/area");

        $response->assertStatus(200)
            ->assertJsonPath('parcel_id', $parcel->id)
            ->assertJsonPath('area_sqm', null)
            ->assertJsonPath('area_hectares', null);
    }

    /**
     * M4: Null geometry handling - verify model's calculateArea returns null.
     */
    public function test_parcel_calculate_area_returns_null_for_null_boundary(): void
    {
        $parcel = Parcel::factory()->create([
            'boundary' => null,
            'centroid' => null,
            'area_sqm' => null,
        ]);

        $area = $parcel->calculateArea();

        $this->assertNull($area, 'Area calculation should return null for null boundary');
    }
}
