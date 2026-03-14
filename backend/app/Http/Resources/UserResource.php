<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return array_filter([
            'id' => $this->id,
            'phone' => $this->phone ?? null,
            'google_id' => $this->google_id ?? null,
            'first_name' => $this->first_name ?? null,
            'last_name' => $this->last_name ?? null,
            'avatar_url' => $this->avatar_url ?? null,
            'dob' => $this->dob?->toDateString() ?? null,
            'profile_complete' => $this->profile_complete ?? null,
            'created_at' => $this->created_at?->toDateTimeString() ?? null,
            'updated_at' => $this->updated_at?->toDateTimeString() ?? null,
        ]);
    }
}