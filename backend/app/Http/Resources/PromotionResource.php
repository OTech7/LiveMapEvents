<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'venue' => $this->whenLoaded('venue', fn() => [
                'id' => $this->venue->id,
                'name' => $this->venue->name,
                'type' => $this->venue->type,
                'address' => $this->venue->address,
                'city' => $this->venue->city,
            ]),
            'title' => $this->title,
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'recurrence_type' => $this->recurrence_type,
            'days_of_week' => $this->days_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_to' => $this->valid_to?->toDateString(),
            'max_total_redemptions' => $this->max_total_redemptions,
            'max_per_user_redemptions' => $this->max_per_user_redemptions,
            'terms' => $this->terms,
            'is_active' => $this->is_active,
            // 'status' is a runtime-computed field, only present on nearby results
            'status' => $this->when(isset($this->resource->status), $this->resource->status ?? null),
            'created_at' => $this->created_at,
        ];
    }
}
