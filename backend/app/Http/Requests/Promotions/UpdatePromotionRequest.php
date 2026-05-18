<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:120',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0.01|max:9999999',
            'recurrence_type' => 'sometimes|in:one_time,recurring',
            'days_of_week' => 'nullable|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_time' => 'sometimes|date_format:H:i,H:i:s',
            'end_time' => 'sometimes|date_format:H:i,H:i:s|after:start_time',
            'valid_from' => 'sometimes|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'max_total_redemptions' => 'nullable|integer|min:1',
            'max_per_user_redemptions' => 'nullable|integer|min:1',
            'terms' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }
}
