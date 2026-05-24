<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venue;

class VenuePolicy
{
    /**
     * Only the owner can view a venue through an owner-scoped endpoint.
     *
     * Note: public discovery endpoints (map / pin listings) should not
     * call authorize() so they remain open to any authenticated user.
     */
    public function view(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    /**
     * Only the owner can update their venue.
     */
    public function update(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    /**
     * Only the owner can delete their venue.
     */
    public function delete(User $user, Venue $venue): bool
    {
        return $this->owns($user, $venue);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function owns(User $user, Venue $venue): bool
    {
        return $venue->owner_id === $user->id;
    }
}
