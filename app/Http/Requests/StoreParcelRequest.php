<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:free,negotiating,target'],
            'price_per_sqm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'geometry' => ['required', 'array'],
            'geometry.type' => ['required', 'string', 'in:Polygon'],
            'geometry.coordinates' => ['required', 'array', 'min:1'],
            'geometry.coordinates.*' => ['required', 'array', 'min:4'],
            'geometry.coordinates.*.*' => ['required', 'array', 'size:2'],
            'geometry.coordinates.*.*.0' => ['required', 'numeric', 'between:-180,180'],
            'geometry.coordinates.*.*.1' => ['required', 'numeric', 'between:-90,90'],
        ];
    }

    public function messages(): array
    {
        return [
            'geometry.required' => 'The geometry field is required',
            'geometry.type.in' => 'Only Polygon geometry is supported',
            'geometry.coordinates.*.*.0.between' => 'Longitude must be between -180 and 180',
            'geometry.coordinates.*.*.1.between' => 'Latitude must be between -90 and 90',
        ];
    }
}
