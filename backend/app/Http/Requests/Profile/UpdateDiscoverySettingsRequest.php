<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscoverySettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'radius' => 'required|integer|min:100|max:5000',
            'notifications' => 'sometimes|boolean',
        ];
    }

}