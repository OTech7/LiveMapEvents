<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Only the owner of the event's venue can view it through an owner-scoped
     * endpoint. Public discovery is not gated by this policy.
     */
    public function view(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    /**
     * Only the owner of the event's venue can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    /**
     * Only the owner of the event's venue can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->owns($user, $event);
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function owns(User $user, Event $event): bool
    {
        // Avoid an extra query every time the policy fires in a loop —
        // load the venue relation only if it's not already eager-loaded.
        if (!$event->relationLoaded('venue')) {
            $event->loadMissing('venue');
        }

        return $event->venue?->owner_id === $user->id;
    }
}
