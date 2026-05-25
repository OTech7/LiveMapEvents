<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Basic info
            'venue_id' => 'required|integer|exists:venues,id',
            'title' => 'required|string|max:80',
            'description' => 'nullable|string|max:4000',
            'category' => 'nullable|string|max:100',
            'image_url' => 'nullable|url|max:2048',

            // Schedule
            'starts_at' => 'required|date|after_or_equal:now',
            'ends_at' => 'nullable|date|after:starts_at',

            // Location
            'is_online_event' => 'boolean',
            'online_event_url' => 'required_if:is_online_event,true|nullable|url|max:2048',

            // Attendance
            'rsvp_limit' => 'nullable|integer|min:1',
            'guest_limit' => 'nullable|integer|min:0|max:10',

            // Publishing
            'publish_status' => 'nullable|in:published,draft',
        ];
    }

    public function messages(): array
    {
        return [
            'online_event_url.required_if' => 'A meeting link is required for online events.',
            'ends_at.after' => 'The end time must be after the start time.',
            'starts_at.after_or_equal' => 'The event cannot be scheduled in the past.',
        ];
    }
}
