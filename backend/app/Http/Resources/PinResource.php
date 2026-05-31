<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PinResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'venue_id' => $this->venue_id,
            'event_id' => $this->event_id,
            'type' => $this->type,
            'has_promotion' => $this->has_promotion,
            'label' => $this->label,
            'lat' => optional($this->location)->getLatitude(),
            'lng' => optional($this->location)->getLongitude(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
