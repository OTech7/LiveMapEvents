<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

class StorePromotionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'venue_id' => 'required|integer|exists:venues,id',
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0.01|max:9999999',
            'recurrence_type' => 'required|in:one_time,recurring',
            'days_of_week' => 'required_if:recurrence_type,recurring|array|min:1',
            'days_of_week.*' => 'integer|between:1,7',
            'start_time' => 'required|date_format:H:i,H:i:s',
            'end_time' => 'required|date_format:H:i,H:i:s|after:start_time',
            'valid_from' => 'required|date|after_or_equal:today',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'max_total_redemptions' => 'nullable|integer|min:1',
            'max_per_user_redemptions' => 'nullable|integer|min:1',
            'terms' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }
}
