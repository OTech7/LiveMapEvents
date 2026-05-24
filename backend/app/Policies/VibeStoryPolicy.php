<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VibeStory;

class VibeStoryPolicy
{
    /**
     * Only the owner of the story's venue can view it through an owner-scoped
     * endpoint. (The user who posted the story is handled separately if needed
     * — this policy is for the business "manage my venue's stories" surface.)
     */
    public function view(User $user, VibeStory $story): bool
    {
        return $this->owns($user, $story);
    }

    /**
     * Only the owner of the story's venue can update it.
     */
    public function update(User $user, VibeStory $story): bool
    {
        return $this->owns($user, $story);
    }

    /**
     * Only the owner of the story's venue can delete it.
     */
    public function delete(User $user, VibeStory $story): bool
    {
        return $this->owns($user, $story);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function owns(User $user, VibeStory $story): bool
    {
        if (!$story->relationLoaded('venue')) {
            $story->loadMissing('venue');
        }

        return $story->venue?->owner_id === $user->id;
    }
}
