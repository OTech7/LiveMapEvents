<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

class PromotionPolicy
{
    /**
     * Any authenticated user can view a single promotion (for the public API).
     */
    public function view(User $user, Promotion $promotion): bool
    {
        return true;
    }

    /**
     * Only the venue owner can see the claims for their promotion.
     */
    public function viewClaims(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    /**
     * Only the venue owner can update their promotion.
     */
    public function update(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    /**
     * Only the venue owner can delete their promotion.
     */
    public function delete(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function owns(User $user, Promotion $promotion): bool
    {
        // Ensure venue is loaded so we don't trigger an extra query
        // every time this policy is checked (e.g., in a loop).
        if (!$promotion->relationLoaded('venue')) {
            $promotion->loadMissing('venue');
        }

        return $promotion->venue->owner_id === $user->id;
    }
}
