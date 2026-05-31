<?php

namespace App\Http\Requests\Venues;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVenueRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:120',
            'type' => 'sometimes|string|max:60',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
