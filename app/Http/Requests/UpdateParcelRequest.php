<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:free,negotiating,target'],
            'price_per_sqm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'geometry' => ['sometimes', 'array'],
            'geometry.type' => ['sometimes', 'string', 'in:Polygon'],
            'geometry.coordinates' => ['sometimes', 'array', 'min:1'],
            'geometry.coordinates.*' => ['sometimes', 'array', 'min:4'],
            'geometry.coordinates.*.*' => ['sometimes', 'array', 'size:2'],
            'geometry.coordinates.*.*.0' => ['sometimes', 'numeric', 'between:-180,180'],
            'geometry.coordinates.*.*.1' => ['sometimes', 'numeric', 'between:-90,90'],
        ];
    }

    public function messages(): array
    {
        return [
            'geometry.type.in' => 'Only Polygon geometry is supported',
            'geometry.coordinates.*.*.0.between' => 'Longitude must be between -180 and 180',
            'geometry.coordinates.*.*.1.between' => 'Latitude must be between -90 and 90',
        ];
    }
}
