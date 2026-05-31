<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VenueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'city' => $this->city,
            'notes' => $this->notes,
            'lat' => $this->location?->getLatitude(),
            'lng' => $this->location?->getLongitude(),
            'is_frozen' => $this->isFrozen(),
            'frozen_at' => $this->frozen_at?->toIso8601String(),
            'freeze_reason' => $this->freeze_reason,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
