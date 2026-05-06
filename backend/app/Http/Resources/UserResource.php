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
            'gender' => $this->gender ?? null,
            'user_type' => $this->user_type ?? null,
            'location' => $this->location ?? null,
            'profile_complete' => $this->profile_complete ?? null,
            // Only include interests when the relation is loaded — avoids
            // an N+1 surprise on endpoints that don't eager-load it.
            'interests' => $this->whenLoaded('interests', fn() => InterestResource::collection($this->interests)),
            'created_at' => $this->created_at?->format('Y-m-d H:m') ?? null,
            'updated_at' => $this->updated_at?->format('Y-m-d H:m') ?? null,
        ], fn($v) => $v !== null);
    }
}
