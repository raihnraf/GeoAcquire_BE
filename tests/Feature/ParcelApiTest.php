<?php

namespace Tests\Feature;

use App\Models\Parcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParcelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_parcels_as_feature_collection(): void
    {
        Parcel::factory()->count(3)->create();

        $response = $this->getJson('/api/parcels');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('FeatureCollection', $data['type']);
        $this->assertCount(3, $data['features']);
    }

    public function test_can_create_parcel_with_geojson_geometry(): void
    {
        $payload = [
            'owner_name' => 'Test Owner',
            'status' => 'target',
            'price_per_sqm' => 12000000,
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

        $response = $this->postJson('/api/parcels', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('type', 'Feature')
            ->assertJsonPath('properties.owner_name', 'Test Owner')
            ->assertJsonPath('properties.status', 'target')
            ->assertJsonPath('geometry.type', 'Polygon');

        $this->assertDatabaseCount('parcels', 1);
    }

    public function test_can_retrieve_single_parcel_as_geojson_feature(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->getJson("/api/parcels/{$parcel->id}");

        $response->assertStatus(200)
            ->assertJsonPath('type', 'Feature')
            ->assertJsonPath('id', $parcel->id)
            ->assertJsonPath('properties.owner_name', $parcel->owner_name)
            ->assertJsonPath('properties.status', $parcel->status)
            ->assertJsonPath('geometry.type', 'Polygon');
    }

    public function test_can_update_parcel(): void
    {
        $parcel = Parcel::factory()->create(['owner_name' => 'Original']);

        $response = $this->putJson("/api/parcels/{$parcel->id}", [
            'owner_name' => 'Updated Owner',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('properties.owner_name', 'Updated Owner');

        $this->assertDatabaseHas('parcels', ['owner_name' => 'Updated Owner']);
    }

    public function test_can_delete_parcel(): void
    {
        $parcel = Parcel::factory()->create();

        $response = $this->deleteJson("/api/parcels/{$parcel->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Parcel deleted successfully');

        $this->assertDatabaseMissing('parcels', ['id' => $parcel->id]);
    }

    public function test_returns_404_for_nonexistent_parcel(): void
    {
        $response = $this->getJson('/api/parcels/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rejects_invalid_geometry(): void
    {
        $payload = [
            'owner_name' => 'Test',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [106.6, -6.25],
            ],
        ];

        $response = $this->postJson('/api/parcels', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Only Polygon geometry is supported (and 2 more errors)');
    }

    public function test_validation_rejects_invalid_coordinates(): void
    {
        $payload = [
            'owner_name' => 'Test',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [999, -6.25],
                    [106.6170, -6.2500],
                    [106.6170, -6.2510],
                    [106.6150, -6.2510],
                    [106.6150, -6.2500],
                ]],
            ],
        ];

        $response = $this->postJson('/api/parcels', $payload);

        $response->assertStatus(422);
    }

    public function test_validation_requires_owner_name(): void
    {
        $payload = [
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

        $response = $this->postJson('/api/parcels', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('errors.owner_name.0', 'The owner name field is required.');
    }
}
