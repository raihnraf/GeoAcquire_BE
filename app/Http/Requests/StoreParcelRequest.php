<?php

namespace App\Http\Requests;

use App\Enums\ParcelStatus;
use App\Rules\GeoJsonPolygon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

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
            'status' => ['sometimes', new Enum(ParcelStatus::class)],
            'price_per_sqm' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'geometry' => ['required', new GeoJsonPolygon],
        ];
    }
}
