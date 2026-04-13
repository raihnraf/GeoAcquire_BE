<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when geometry data is invalid or cannot be parsed.
 *
 * Examples:
 *  - GeoJSON type is not "Polygon"
 *  - Coordinate values are out of valid geographic range
 *  - Ring has fewer than 4 coordinates
 *  - Self-intersecting polygon detected
 */
class InvalidGeometryException extends RuntimeException
{
    public static function unsupportedType(string $type): self
    {
        return new self("Unsupported geometry type '{$type}'. Only 'Polygon' is accepted.");
    }

    public static function invalidCoordinates(string $reason): self
    {
        return new self("Invalid geometry coordinates: {$reason}");
    }

    public static function selfIntersecting(): self
    {
        return new self('Invalid geometry: self-intersecting (bowtie) polygons are not allowed.');
    }

    public static function invalidRing(int $ringIndex, string $reason): self
    {
        return new self("Invalid geometry ring [{$ringIndex}]: {$reason}");
    }
}