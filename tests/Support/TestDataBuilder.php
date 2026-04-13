<?php

namespace Tests\Support;

use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

/**
 * Test data builder for creating common test geometries and parcels.
 * Reduces duplication across test files by providing reusable coordinate patterns.
 */
class TestDataBuilder
{
    /**
     * Base coordinates for Jakarta area test parcels.
     */
    private const BASE_LAT = -6.2500;
    private const BASE_LNG = 106.6150;
    private const SIZE = 0.001;

    /**
     * Create a standard square polygon for testing.
     * Coordinates are centered around Jakarta (Monas area).
     *
     * @param array{lat?: float, lng?: float, size?: float} $options
     * @return Polygon
     */
    public static function standardPolygon(array $options = []): Polygon
    {
        $lat = $options['lat'] ?? self::BASE_LAT;
        $lng = $options['lng'] ?? self::BASE_LNG;
        $size = $options['size'] ?? self::SIZE;

        return new Polygon([
            new LineString([
                new Point($lat, $lng),
                new Point($lat, $lng + $size),
                new Point($lat + $size, $lng + $size),
                new Point($lat + $size, $lng),
                new Point($lat, $lng),
            ]),
        ]);
    }

    /**
     * Create a polygon located far from the standard test area.
     * Useful for testing distance/buffer queries.
     */
    public static function farPolygon(): Polygon
    {
        return new Polygon([
            new LineString([
                new Point(-6.5000, 107.0000),
                new Point(-6.5000, 107.0100),
                new Point(-6.5100, 107.0100),
                new Point(-6.5100, 107.0000),
                new Point(-6.5000, 107.0000),
            ]),
        ]);
    }

    /**
     * Create GeoJSON coordinates for a standard square polygon.
     * Returns in GeoJSON format: [[lng, lat], [lng, lat], ...]
     *
     * @param array{lat?: float, lng?: float, size?: float} $options
     * @return list<list<float>>
     */
    public static function standardGeoJsonCoordinates(array $options = []): array
    {
        $lat = $options['lat'] ?? self::BASE_LAT;
        $lng = $options['lng'] ?? self::BASE_LNG;
        $size = $options['size'] ?? self::SIZE;

        return [
            [$lng, $lat],
            [$lng + $size, $lat],
            [$lng + $size, $lat + $size],
            [$lng, $lat + $size],
            [$lng, $lat],
        ];
    }

    /**
     * Create a full GeoJSON geometry object for a standard polygon.
     *
     * @param array{lat?: float, lng?: float, size?: float} $options
     * @return array{type: 'Polygon', coordinates: list<list<float>>}
     */
    public static function standardGeoJsonGeometry(array $options = []): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [self::standardGeoJsonCoordinates($options)],
        ];
    }

    /**
     * Create GeoJSON coordinates for a polygon with holes.
     * Useful for testing polygon with interior rings.
     */
    public static function polygonWithHolesGeoJsonCoordinates(): array
    {
        return [
            // Outer ring
            [
                [106.6150, -6.2500],
                [106.6170, -6.2500],
                [106.6170, -6.2520],
                [106.6150, -6.2520],
                [106.6150, -6.2500],
            ],
            // Inner ring (hole)
            [
                [106.6155, -6.2505],
                [106.6165, -6.2505],
                [106.6165, -6.2515],
                [106.6155, -6.2515],
                [106.6155, -6.2505],
            ],
        ];
    }

    /**
     * Create coordinates for a self-intersecting (bowtie) polygon.
     * This is an invalid geometry per OGC Simple Features spec.
     * Useful for testing validation of invalid geometries.
     */
    public static function selfIntersectingGeoJsonCoordinates(): array
    {
        return [
            [106.6150, -6.2500],
            [106.6170, -6.2520],
            [106.6170, -6.2500],
            [106.6150, -6.2520],
            [106.6150, -6.2500],
        ];
    }

    /**
     * Create coordinates for a polygon with only 3 points (triangle).
     * This is an invalid polygon because rings must have ≥4 coordinates.
     * Useful for testing polygon ring validation.
     */
    public static function triangleGeoJsonCoordinates(): array
    {
        return [
            [106.6150, -6.2500],
            [106.6170, -6.2500],
            [106.6160, -6.2520],
            [106.6150, -6.2500],  // Only 3 unique points (4 with closure)
        ];
    }

    /**
     * Create coordinates for a polygon with an unclosed ring.
     * First and last coordinates don't match - invalid per GeoJSON spec.
     */
    public static function unclosedRingGeoJsonCoordinates(): array
    {
        return [
            [106.6150, -6.2500],
            [106.6170, -6.2500],
            [106.6170, -6.2520],
            [106.6150, -6.2520],
            // Missing closure to first point
        ];
    }

    /**
     * Get a bounding box that contains the standard polygon.
     * Returns [minLng, minLat, maxLng, maxLat].
     */
    public static function standardBoundingBox(): array
    {
        return [
            106.600,  // minLng
            -6.300,   // minLat
            106.650,  // maxLng
            -6.200,   // maxLat
        ];
    }

    /**
     * Get a bounding box that does NOT contain the standard polygon.
     * Useful for testing empty query results.
     */
    public static function farBoundingBox(): array
    {
        return [
            107.0,   // minLng (far away)
            -6.300,  // minLat
            107.5,   // maxLng
            -6.200,  // maxLat
        ];
    }

    /**
     * Get centroid coordinates for the standard polygon.
     * Returns [lat, lng] for Point constructor.
     */
    public static function standardCentroid(): array
    {
        return [
            'lat' => self::BASE_LAT + (self::SIZE / 2),
            'lng' => self::BASE_LNG + (self::SIZE / 2),
        ];
    }

    /**
     * Get valid status values for parcels.
     */
    public static function validStatuses(): array
    {
        return ['free', 'negotiating', 'target'];
    }

    /**
     * Get an invalid status value for testing validation.
     */
    public static function invalidStatus(): string
    {
        return 'nonexistent';
    }
}
