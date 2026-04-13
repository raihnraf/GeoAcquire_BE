<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeoJsonPolygon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = $this->validateGeometry($value);

        if ($error !== null) {
            $fail($error);
        }
    }

    /**
     * Validate GeoJSON geometry and return error message, or null if valid.
     * Shared method for use in both ValidationRule and direct validation.
     */
    public static function validateGeometry(mixed $geometry): ?string
    {
        if (!is_array($geometry)) {
            return 'The geometry must be a valid GeoJSON Polygon object.';
        }

        if (!isset($geometry['type']) || $geometry['type'] !== 'Polygon') {
            return 'The geometry must be of type Polygon.';
        }

        if (!isset($geometry['coordinates']) || !is_array($geometry['coordinates'])) {
            return 'The geometry must contain coordinates.';
        }

        if (count($geometry['coordinates']) < 1) {
            return 'The geometry must have at least one ring.';
        }

        foreach ($geometry['coordinates'] as $ringIndex => $ring) {
            if (!is_array($ring) || count($ring) < 4) {
                return "Ring {$ringIndex} must have at least 4 coordinates.";
            }

            foreach ($ring as $coordIndex => $coord) {
                if (!is_array($coord) || count($coord) !== 2) {
                    return "Coordinate [{$ringIndex}][{$coordIndex}] must be a [longitude, latitude] pair.";
                }

                $lng = $coord[0];
                $lat = $coord[1];

                if (!is_numeric($lng)) {
                    return "Longitude at [{$ringIndex}][{$coordIndex}] must be a numeric value.";
                }

                $lngError = CoordinateRange::validateValue((float)$lng, 'longitude');
                if ($lngError !== null) {
                    return "Longitude at [{$ringIndex}][{$coordIndex}]: {$lngError}";
                }

                if (!is_numeric($lat)) {
                    return "Latitude at [{$ringIndex}][{$coordIndex}] must be a numeric value.";
                }

                $latError = CoordinateRange::validateValue((float)$lat, 'latitude');
                if ($latError !== null) {
                    return "Latitude at [{$ringIndex}][{$coordIndex}]: {$latError}";
                }
            }
        }

        return null;
    }
}