<?php

namespace App\Http\Requests;

use App\Rules\CoordinateRange;
use Illuminate\Foundation\Http\FormRequest;

class BufferAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lng' => ['required', 'numeric', new CoordinateRange('longitude')],
            'lat' => ['required', 'numeric', new CoordinateRange('latitude')],
            'distance' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }
}