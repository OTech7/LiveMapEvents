<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EventService
{
    /**
     * List all events belonging to venues owned by the given user.
     * Optionally scoped to a single venue.
     */
    public function getForOwner(User $user, ?int $venueId = null): LengthAwarePaginator
    {
        $query = Event::query()
            ->whereHas('venue', fn($q) => $q->where('owner_id', $user->id))
            ->with('venue');

        if ($venueId) {
            $query->where('venue_id', $venueId);
        }

        return $query->orderByDesc('starts_at')->paginate(20);
    }

    /**
     * Create a new event under a venue owned by the user.
     *
     * @throws ApiException
     */
    public function create(User $user, array $data): Event
    {
        $venue = $this->resolveOwnedVenue($user, $data['venue_id']);

        return DB::transaction(function () use ($venue, $data) {
            $endsAt = isset($data['ends_at'])
                ? Carbon::parse($data['ends_at'])
                : Carbon::parse($data['starts_at'])->addHours(3);

            return Event::create([
                'venue_id' => $venue->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'starts_at' => Carbon::parse($data['starts_at']),
                'ends_at' => $endsAt,
                'is_free' => true,           // fees are out of scope
                'is_online_event' => $data['is_online_event'] ?? false,
                'online_event_url' => $data['online_event_url'] ?? null,
                'rsvp_limit' => $data['rsvp_limit'] ?? null,
                'guest_limit' => $data['guest_limit'] ?? 0,
                'publish_status' => $data['publish_status'] ?? 'published',
            ]);
        });
    }

    /**
     * Update an existing event. Venue cannot be changed after creation.
     */
    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            // If starts_at changes and ends_at is not supplied, recalculate ends_at
            if (isset($data['starts_at']) && !isset($data['ends_at'])) {
                $data['ends_at'] = Carbon::parse($data['starts_at'])->addHours(3);
            }

            // Use validated data directly — UpdateEventRequest uses `sometimes`,
            // so only keys present in the request payload reach here.
            // We must NOT strip null values: setting rsvp_limit => null
            // is a valid operation to remove the attendee cap.
            $event->update($data);

            return $event->fresh('venue');
        });
    }

    /**
     * Soft-cancel an event — sets publish_status to 'cancelled' (not a hard delete).
     * A reason string is stored in the description suffix for audit purposes.
     *
     * @throws ApiException
     */
    public function cancel(Event $event, ?string $reason = null): Event
    {
        if ($event->publish_status === 'cancelled') {
            throw new ApiException(__('messages.event_already_cancelled'), 409);
        }

        $event->update(['publish_status' => 'cancelled']);

        // Persist the cancellation reason as an audit note (no separate table needed yet)
        if ($reason) {
            $event->update([
                'description' => $event->description
                    . "\n\n[Cancellation reason: {$reason}]",
            ]);
        }

        return $event->fresh('venue');
    }

    /**
     * Hard-delete an event.
     *
     * @throws ApiException
     */
    public function delete(Event $event): void
    {
        DB::transaction(fn() => $event->delete());
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * Resolve a venue that exists and is owned by the given user.
     *
     * @throws ApiException
     */
    private function resolveOwnedVenue(User $user, int $venueId): Venue
    {
        $venue = Venue::find($venueId);

        if (!$venue || $venue->owner_id !== $user->id) {
            throw new ApiException(__('messages.venue_not_found_or_unauthorized'), 403);
        }

        if ($venue->isFrozen()) {
            throw new ApiException(__('messages.venue_frozen'), 403);
        }

        return $venue;
    }
}
