<?php

namespace App\Http\Requests\Pins;

use Illuminate\Foundation\Http\FormRequest;

class NearbyPinsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:100|max:50000',
            'types' => 'sometimes|array',
            'types.*' => 'string',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer',
        ];
    }
}
