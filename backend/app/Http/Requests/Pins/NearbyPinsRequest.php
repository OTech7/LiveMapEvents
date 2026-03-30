<?php

namespace App\Http\Requests\Pins;

use Illuminate\Foundation\Http\FormRequest;

class NearbyPinsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'lat'        => 'required|numeric',
            'lng'        => 'required|numeric',
            'radius'     => 'required|numeric',
            'types'      => 'sometimes|array',
            'types.*'    => 'string',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer'
        ];
    }
}