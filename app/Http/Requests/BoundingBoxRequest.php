<?php

namespace App\Http\Requests;

use App\Enums\ParcelStatus;
use Illuminate\Foundation\Http\FormRequest;

class BoundingBoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bbox' => ['nullable', 'regex:/^\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?$/'],
            'status' => ['nullable', 'in:' . implode(',', ParcelStatus::values())],
            'limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        // Custom validation for multiple status parameters
        // Laravel's 'in:' rule only works for single values, so we validate manually
        $this->merge([
            'status_array' => $this->parseStatusArray(),
        ]);
        
        $validator->after(function ($validator) {
            // Validate bbox format
            $bbox = $this->input('bbox');
            if (! $bbox) {
                return;
            }

            // Check bbox format before parsing - regex validation may have failed
            if (! preg_match('/^\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?$/', $bbox)) {
                return; // Let regex validation handle the error
            }

            $coords = explode(',', $bbox);
            $minLng = (float) $coords[0];
            $minLat = (float) $coords[1];
            $maxLng = (float) $coords[2];
            $maxLat = (float) $coords[3];

            // Validate longitude range (-180 to 180)
            if ($minLng < -180 || $minLng > 180 || $maxLng < -180 || $maxLng > 180) {
                $validator->errors()->add('bbox', 'Longitude values must be between -180 and 180');
            }

            // Validate latitude range (-90 to 90)
            if ($minLat < -90 || $minLat > 90 || $maxLat < -90 || $maxLat > 90) {
                $validator->errors()->add('bbox', 'Latitude values must be between -90 and 90');
            }

            // Validate min < max for both coordinates
            if ($minLng >= $maxLng) {
                $validator->errors()->add('bbox', 'minLng must be less than maxLng');
            }

            if ($minLat >= $maxLat) {
                $validator->errors()->add('bbox', 'minLat must be less than maxLat');
            }
        });
    }
    
    /**
     * Parse status parameter(s) from query string.
     * Handles both multiple params (?status=free&status=negotiating) 
     * and comma-separated (?status=free,negotiating).
     */
    private function parseStatusArray(): array
    {
        // Get raw query string from $_SERVER
        $rawQueryString = $_SERVER['QUERY_STRING'] ?? '';
        
        // DEBUG: Log the raw query string
        \Log::debug('Raw Query String: ' . $rawQueryString);
        \Log::debug('getQueryString(): ' . ($this->getQueryString() ?? 'null'));
        
        $queryString = $rawQueryString ?: ($this->getQueryString() ?? '');
        if (!$queryString) {
            return [];
        }
        
        $statuses = [];
        $pairs = explode('&', $queryString);
        
        \Log::debug('Pairs count: ' . count($pairs));
        
        foreach ($pairs as $pair) {
            if (strpos($pair, '=') !== false) {
                [$key, $value] = explode('=', $pair, 2);
                $key = urldecode($key);
                $value = urldecode($value);
                
                \Log::debug("Pair - Key: {$key}, Value: {$value}");
                
                if ($key === 'status' && $value !== '') {
                    // Handle both 'status=free,negotiating' and 'status=free'
                    $values = explode(',', $value);
                    foreach ($values as $v) {
                        $trimmed = strtolower(trim($v));
                        if ($trimmed !== '' && in_array($trimmed, ParcelStatus::values(), true)) {
                            $statuses[] = $trimmed;
                        }
                    }
                }
            }
        }
        
        $uniqueStatuses = array_values(array_unique($statuses));
        \Log::debug('Parsed statuses: ' . json_encode($uniqueStatuses));
        
        return $uniqueStatuses;
    }
    
    /**
     * Get parsed status array from request.
     */
    public function getStatusArray(): array
    {
        return $this->input('status_array', []);
    }

    public function getBoundingBox(): ?array
    {
        $bbox = $this->input('bbox');
        if (! $bbox) {
            return null;
        }

        // Validate bbox format before parsing
        if (! preg_match('/^\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?$/', $bbox)) {
            return null;
        }

        $coords = explode(',', $bbox);

        return [
            (float) $coords[0], // minLng
            (float) $coords[1], // minLat
            (float) $coords[2], // maxLng
            (float) $coords[3], // maxLat
        ];
    }
}
