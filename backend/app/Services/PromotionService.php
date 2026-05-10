<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PromotionService
{
    /**
     * List all promotions for venues owned by the given user.
     */
    public function getForOwner(User $user, ?int $venueId = null): LengthAwarePaginator
    {
        $query = Promotion::whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->with('venue:id,name');

        if ($venueId) {
            $query->where('venue_id', $venueId);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Create a new promotion. Validates the venue belongs to the owner.
     */
    public function create(User $owner, array $data): Promotion
    {
        Venue::where('id', $data['venue_id'])
            ->where('owner_id', $owner->id)
            ->firstOrFail();

        if (($data['discount_type'] ?? null) === 'percentage' && $data['discount_value'] > 100) {
            abort(422, __('messages.discount_percentage_exceeded'));
        }

        return Promotion::create($data);
    }

    /**
     * Update a promotion the owner controls.
     */
    public function update(User $owner, Promotion $promotion, array $data): Promotion
    {
        $this->assertOwns($owner, $promotion);

        $promotion->update($data);

        return $promotion->fresh()->load('venue:id,name');
    }

    /**
     * Soft-delete a promotion.
     */
    public function delete(User $owner, Promotion $promotion): void
    {
        $this->assertOwns($owner, $promotion);

        $promotion->delete();
    }

    /**
     * Fetch nearby promotions that are active or upcoming today.
     */
    public function getNearby(array $data): Collection
    {
        $lat = $data['lat'];
        $lng = $data['lng'];
        $radius = $data['radius'] ?? 5000;

        $today = now()->toDateString();
        $todayIso = now()->dayOfWeekIso;

        return Promotion::query()
            ->where('is_active', true)
            ->where('valid_from', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today);
            })
            ->where(function ($q) use ($todayIso) {
                $q->where('recurrence_type', 'one_time')
                    ->orWhere(function ($q2) use ($todayIso) {
                        $q2->where('recurrence_type', 'recurring')
                            ->whereJsonContains('days_of_week', $todayIso);
                    });
            })
            ->whereHas('venue', function ($q) use ($lat, $lng, $radius) {
                $q->whereRaw(
                    'ST_DWithin(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                    [$lng, $lat, $radius]
                );
            })
            ->with(['venue:id,name,type,address,city,location'])
            ->get()
            ->map(function ($promo) {
                $promo->status = $promo->isActiveNow() ? 'active' : 'upcoming';
                return $promo;
            })
            ->sortByDesc(fn($p) => $p->status === 'active' ? 1 : 0)
            ->values();
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function assertOwns(User $owner, Promotion $promotion): void
    {
        if ($promotion->venue->owner_id !== $owner->id) {
            abort(403, __('messages.promotion_not_owned'));
        }
    }
}
