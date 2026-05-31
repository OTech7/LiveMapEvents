<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            // Venue — only included when eager-loaded
            'venue' => $this->whenLoaded('venue', fn() => [
                'id' => $this->venue->id,
                'name' => $this->venue->name,
                'type' => $this->venue->type,
                'address' => $this->venue->address,
                'city' => $this->venue->city,
                'lat' => optional($this->venue->location)->latitude,
                'lng' => optional($this->venue->location)->longitude,
            ]),

            // Core info
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'image_url' => $this->image_url,

            // Schedule
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),

            // Location
            'is_online_event' => $this->is_online_event,
            'online_event_url' => $this->online_event_url,

            // Attendance
            'is_free' => $this->is_free,
            'rsvp_limit' => $this->rsvp_limit,   // null = unlimited
            'guest_limit' => $this->guest_limit,

            // Status / workflow
            'publish_status' => $this->publish_status,
            'is_active' => $this->is_active,   // true = published + currently in progress

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
