<?php

namespace App\Http\Requests;

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
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'distance' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
