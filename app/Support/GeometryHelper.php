<?php

namespace App\Support;

use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

/**
 * Static helpers for common geometry operations.
 *
 * All coordinate arrays use GeoJSON order: [longitude, latitude].
 */
class GeometryHelper
{
    /**
     * Calculate the centroid of a polygon ring using the arithmetic mean of vertices.
     *
     * @param  array<int, array{0: float, 1: float}>  $coordinates  GeoJSON-style [lng, lat] pairs
     */
    public static function centroidFromCoordinates(array $coordinates): Point
    {
        $count = count($coordinates);

        $lngSum = 0.0;
        $latSum = 0.0;

        foreach ($coordinates as $coord) {
            $lngSum += $coord[0]; // lng
            $latSum += $coord[1]; // lat
        }

        return new Point($latSum / $count, $lngSum / $count);
    }

    /**
     * Build a Polygon from one or more GeoJSON-style coordinate rings.
     *
     * @param  array<int, array<int, array{0: float, 1: float}>>  $rings  Each ring is a list of [lng, lat] pairs
     */
    public static function polygonFromCoordinates(array $rings): Polygon
    {
        $lineStrings = array_map(
            fn (array $ring) => new LineString(
                array_map(fn ($c) => new Point($c[1], $c[0]), $ring)
            ),
            $rings
        );

        return new Polygon($lineStrings);
    }
}
