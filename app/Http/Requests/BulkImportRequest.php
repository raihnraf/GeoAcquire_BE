<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:FeatureCollection'],
            'features' => ['required', 'array', 'min:1', 'max:100'],
            'features.*.type' => ['required', 'in:Feature'],
            'features.*.geometry' => ['required', 'array'],
            'features.*.geometry.type' => ['required', 'in:Polygon'],
            'features.*.geometry.coordinates' => ['required', 'array'],
            'features.*.properties' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The type must be FeatureCollection.',
            'features.max' => 'Cannot import more than 100 features in a single request.',
            'features.*.geometry.type.in' => 'Only Polygon geometry is supported.',
        ];
    }
}
