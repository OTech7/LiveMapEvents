<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterestsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'interests'   => 'required|array|min:1|max:10',
            'interests.*' => 'exists:interests,slug'
        ];
    }
}