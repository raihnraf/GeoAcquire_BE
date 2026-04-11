<?php

namespace App\Http\Requests;

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
            'bbox' => ['required', 'regex:/^\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?,\-?\d+(\.\d+)?$/'],
            'status' => ['nullable', 'in:free,negotiating,target'],
        ];
    }

    public function getBoundingBox(): array
    {
        $coords = explode(',', $this->input('bbox'));

        return [
            (float) $coords[0], // minLng
            (float) $coords[1], // minLat
            (float) $coords[2], // maxLng
            (float) $coords[3], // maxLat
        ];
    }
}
