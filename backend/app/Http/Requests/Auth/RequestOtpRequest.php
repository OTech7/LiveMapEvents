<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNormalizer::normalize((string) $this->phone),
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
        ];
    }
}