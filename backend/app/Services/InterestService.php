<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class InterestService
{
    /**
     * Return all interests ordered alphabetically.
     */
    public function getAll(): Collection
    {
        return Interest::query()->orderBy('name')->get();
    }

    /**
     * Return the interests currently attached to the given user.
     */
    public function getForUser(User $user): Collection
    {
        return $user->interests;
    }

    /**
     * Resolve interest IDs from an array of slugs and sync them to the user,
     * replacing any previously attached interests.
     */
    public function syncBySlug(User $user, array $slugs): void
    {
        $ids = Interest::whereIn('slug', $slugs)->pluck('id')->toArray();
        $user->interests()->sync($ids);
    }

    /**
     * Attach a single interest to the user without detaching existing ones.
     */
    public function attach(User $user, Interest $interest): void
    {
        $user->interests()->syncWithoutDetaching([$interest->id]);
    }

    /**
     * Detach a single interest from the user.
     */
    public function detach(User $user, Interest $interest): void
    {
        $user->interests()->detach($interest->id);
    }
}
