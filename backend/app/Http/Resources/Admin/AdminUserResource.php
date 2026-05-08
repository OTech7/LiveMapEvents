<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin-flavoured user payload. Differs from the public UserResource:
 *   - exposes id always
 *   - includes role names
 *   - exposes timestamps in full ISO-8601 (the panel formats them)
 */
class AdminUserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'google_id' => $this->google_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar_url' => $this->avatar_url,
            'dob' => $this->dob?->toDateString(),
            'gender' => $this->gender,
            'user_type' => $this->user_type,
            'profile_complete' => (bool)$this->profile_complete,
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            // Interest ids as strings — matches the AutoForm multi-select's
            // option `value` shape (which uses string ids), so the form
            // pre-selects the user's current interests on load.
            'interests' => $this->whenLoaded(
                'interests',
                fn() => $this->interests->pluck('id')->map(fn($id) => (string)$id)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
