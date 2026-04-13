<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a coordinate value falls within the valid geographic range.
 *
 * Usage:
 *   new CoordinateRange('longitude')  → validates -180 to 180
 *   new CoordinateRange('latitude')   → validates -90 to 90
 */
class CoordinateRange implements ValidationRule
{
    private const LONGITUDE_MIN = -180;
    private const LONGITUDE_MAX = 180;
    private const LATITUDE_MIN = -90;
    private const LATITUDE_MAX = 90;

    private float $min;
    private float $max;
    private string $label;

    public function __construct(string $type)
    {
        if ($type === 'longitude') {
            $this->min = self::LONGITUDE_MIN;
            $this->max = self::LONGITUDE_MAX;
            $this->label = 'longitude';
        }
        else {
            $this->min = self::LATITUDE_MIN;
            $this->max = self::LATITUDE_MAX;
            $this->label = 'latitude';
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail("The :attribute must be a numeric {$this->label} value.");

            return;
        }

        $value = (float)$value;

        if ($value < $this->min || $value > $this->max) {
            $fail("The :attribute must be a valid {$this->label} between {$this->min} and {$this->max}.");
        }
    }

    /**
     * Validate a raw coordinate value (for use outside of FormRequest context).
     *
     * @return string|null Error message, or null if valid.
     */
    public static function validateValue(float $value, string $type): ?string
    {
        if ($type === 'longitude') {
            if ($value < self::LONGITUDE_MIN || $value > self::LONGITUDE_MAX) {
                return "Longitude value must be between " . self::LONGITUDE_MIN . " and " . self::LONGITUDE_MAX . ".";
            }
        }
        else {
            if ($value < self::LATITUDE_MIN || $value > self::LATITUDE_MAX) {
                return "Latitude value must be between " . self::LATITUDE_MIN . " and " . self::LATITUDE_MAX . ".";
            }
        }

        return null;
    }
}