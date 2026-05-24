<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Support\Concerns\EnsuresRelationLoaded;

class PromotionPolicy
{
    use EnsuresRelationLoaded;

    /**
     * Only the venue owner can view a promotion *through the business API*.
     *
     * NOTE: This policy method is only consulted when a controller explicitly
     * calls `$this->authorize('view', $promotion)` — e.g. the business
     * `show()` endpoint. The PUBLIC PromotionController does not call
     * authorize() and therefore remains open to any authenticated user,
     * which is the intended discovery behaviour.
     */
    public function view(User $user, Promotion $promotion): bool
    {
        return $this->owns($user, $promotion);
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
        $this->ensureLoaded($promotion, 'venue');

        return $promotion->venue->owner_id === $user->id;
    }
}
