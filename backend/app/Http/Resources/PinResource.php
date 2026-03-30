<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PinResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'type'      => $this->type,
            'category'  => $this->category_id,
            'lat'       => $this->location?->getLat(),
            'lng'       => $this->location?->getLng(),
            'distance'  => $this->distance ?? null,
        ];
    }
}