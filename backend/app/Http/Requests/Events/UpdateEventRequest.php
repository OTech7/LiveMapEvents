<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic info
            'title' => 'sometimes|string|max:80',
            'description' => 'sometimes|nullable|string|max:4000',
            'category' => 'sometimes|nullable|string|max:100',
            'image_url' => 'sometimes|nullable|url|max:2048',

            // Schedule
            'starts_at' => 'sometimes|date|after_or_equal:now',
            'ends_at' => 'sometimes|nullable|date|after:starts_at',

            // Location
            'is_online_event' => 'sometimes|boolean',
            'online_event_url' => 'required_if:is_online_event,true|nullable|url|max:2048',

            // Attendance
            'rsvp_limit' => 'sometimes|nullable|integer|min:1',
            'guest_limit' => 'sometimes|nullable|integer|min:0|max:10',

            // Publishing
            'publish_status' => 'sometimes|in:published,draft',
        ];
    }

    public function messages(): array
    {
        return [
            'online_event_url.required_if' => 'A meeting link is required for online events.',
            'ends_at.after' => 'The end time must be after the start time.',
        ];
    }
}
