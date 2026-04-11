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
            // Geometry type and coordinates validation moved to service layer
            // to enable partial success pattern for bulk imports
            'features.*.properties' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The type must be FeatureCollection.',
            'features.max' => 'Cannot import more than 100 features in a single request.',
        ];
    }
}
