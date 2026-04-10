<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_calculate_parcel_area(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->getJson("/api/parcels/{$parcel->id}/area");

        $response->assertStatus(200)
            ->assertJsonPath('parcel_id', $parcel->id)
            ->assertJsonStructure([
                'parcel_id',
                'area_sqm',
                'area_hectares',
            ]);

        $this->assertGreaterThan(0, $response->json('area_sqm'));
        $this->assertGreaterThan(0, $response->json('area_hectares'));
    }

    public function test_area_hectares_is_area_sqm_divided_by_10000(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->getJson("/api/parcels/{$parcel->id}/area");

        $areaSqm = $response->json('area_sqm');
        $areaHectares = $response->json('area_hectares');

        $this->assertEqualsWithDelta($areaSqm / 10000, $areaHectares, 0.001);
    }

    public function test_area_returns_404_for_nonexistent_parcel(): void
    {
        $response = $this->getJson('/api/parcels/99999/area');

        $response->assertStatus(404);
    }
}
