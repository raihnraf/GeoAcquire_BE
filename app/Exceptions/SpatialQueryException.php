<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a spatial database query fails or receives invalid parameters.
 *
 * Examples:
 *  - Bounding box has invalid coordinate order (minLng >= maxLng)
 *  - Buffer distance is out of the allowed range
 *  - Spatial function returns an unexpected result
 */
class SpatialQueryException extends RuntimeException
{
    public static function invalidBoundingBox(string $reason): self
    {
        return new self("Invalid bounding box: {$reason}");
    }

    public static function invalidBufferDistance(int $distance, int $min, int $max): self
    {
        return new self(
            "Buffer distance {$distance}m is out of range. Must be between {$min}m and {$max}m."
            );
    }

    public static function missingGeometry(int $parcelId): self
    {
        return new self("Parcel [{$parcelId}] has no geometry. Cannot perform spatial query.");
    }
}