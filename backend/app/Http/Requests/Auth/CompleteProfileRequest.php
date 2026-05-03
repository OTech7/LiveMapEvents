<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Only normalize the phone if the client actually sent one.
        // Otherwise we end up storing/validating an empty string.
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => PhoneNormalizer::normalize((string)$this->phone),
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $hasPhone = $user && !empty($user->phone);

        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            // Phone is only required for users who don't already have one
            // (e.g. Google sign-in). Phone-OTP users already have it persisted
            // at OTP verification time, so we don't ask again.
            'phone' => [
                $hasPhone ? 'prohibited' : 'required',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],
            'gender' => 'required|in:male,female',
            'dob' => 'required|date|before:' . now()->subYears(16)->toDateString(),
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'avatar_url' => 'nullable|string|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.prohibited' => __('messages.phone_already_set'),
        ];
    }
}
