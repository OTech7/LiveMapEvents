<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\Venue;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VenueService
{
    /**
     * List all venues owned by the given user (paginated).
     */
    public function getForOwner(User $user): LengthAwarePaginator
    {
        return Venue::where('owner_id', $user->id)
            ->orderBy('name')
            ->paginate(20);
    }

    /**
     * Create a new venue owned by the user.
     */
    public function create(User $user, array $data): Venue
    {
        return Venue::create([
            'owner_id' => $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'notes' => $data['notes'] ?? null,
            'location' => $this->makePointFromData($data),
        ]);
    }

    /**
     * Update an existing venue.
     *
     * @throws ApiException
     */
    public function update(Venue $venue, array $data): Venue
    {
        $updates = array_filter([
            'name' => $data['name'] ?? null,
            'type' => $data['type'] ?? null,
            'address' => array_key_exists('address', $data) ? $data['address'] : $venue->address,
            'city' => array_key_exists('city', $data) ? $data['city'] : $venue->city,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $venue->notes,
        ], fn($v) => $v !== null);

        // Handle location separately — it can legitimately become null
        if (array_key_exists('lat', $data) || array_key_exists('lng', $data)) {
            $updates['location'] = $this->makePointFromData($data);
        }

        $venue->fill($updates)->save();

        return $venue->fresh();
    }

    /**
     * Delete a venue. Fails if the venue has active (published) events.
     *
     * @throws ApiException
     */
    public function delete(Venue $venue): void
    {
        $hasActiveEvents = $venue->events()
            ->where('publish_status', 'published')
            ->where('starts_at', '>', now())
            ->exists();

        if ($hasActiveEvents) {
            throw new ApiException('messages.venue_has_active_events', 422);
        }

        $venue->delete();
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function makePointFromData(array $data): ?Point
    {
        if (!isset($data['lat']) || !isset($data['lng'])) {
            return null;
        }

        // Magellan's makeGeodetic expects (latitude, longitude)
        return Point::makeGeodetic((float)$data['lat'], (float)$data['lng']);
    }
}
