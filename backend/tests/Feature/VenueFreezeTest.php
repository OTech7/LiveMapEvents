<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Pin;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Venue;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueFreezeTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639600' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makeVenueFor(User $owner, array $overrides = []): Venue
    {
        return Venue::create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Test Venue',
            'type' => 'bar',
        ], $overrides));
    }

    private function freezeVenue(Venue $venue, string $reason = 'Test freeze'): void
    {
        $venue->update([
            'frozen_at' => now(),
            'freeze_reason' => $reason,
        ]);
    }

    private function makePromotion(Venue $venue, array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Happy Hour',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'recurrence_type' => 'one_time',
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'valid_from' => now()->toDateString(),
            'is_active' => true,
        ], $overrides));
    }

    // ─── Venue model: isFrozen() ──────────────────────────────────────────────

    public function test_is_frozen_returns_false_when_frozen_at_is_null(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->assertFalse($venue->isFrozen());
    }

    public function test_is_frozen_returns_true_when_frozen_at_is_set(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue);

        $this->assertTrue($venue->fresh()->isFrozen());
    }

    public function test_unfreeze_clears_frozen_at_and_reason(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue, 'Spam reports');

        $venue->update(['frozen_at' => null, 'freeze_reason' => null]);
        $venue->refresh();

        $this->assertFalse($venue->isFrozen());
        $this->assertNull($venue->frozen_at);
        $this->assertNull($venue->freeze_reason);
    }

    // ─── VenueResource response ───────────────────────────────────────────────

    public function test_venue_resource_returns_is_frozen_false_for_active_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk()
            ->assertJsonPath('data.is_frozen', false)
            ->assertJsonPath('data.frozen_at', null)
            ->assertJsonPath('data.freeze_reason', null);
    }

    public function test_venue_resource_returns_is_frozen_true_with_details_when_frozen(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue, 'Policy violation');

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk()
            ->assertJsonPath('data.is_frozen', true)
            ->assertJsonPath('data.freeze_reason', 'Policy violation');

        // frozen_at should be a non-null ISO timestamp
        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/venues/{$venue->id}");
        $this->assertNotNull($response->json('data.frozen_at'));
    }

    // ─── Event creation blocked on frozen venue ───────────────────────────────

    public function test_cannot_create_event_for_frozen_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', [
                'venue_id' => $venue->id,
                'title' => 'New Event',
                'starts_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertForbidden();
    }

    public function test_can_create_event_for_unfrozen_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        // not frozen

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', [
                'venue_id' => $venue->id,
                'title' => 'New Event',
                'starts_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated();
    }

    public function test_can_create_event_after_venue_is_unfrozen(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue);
        $venue->update(['frozen_at' => null, 'freeze_reason' => null]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', [
                'venue_id' => $venue->id,
                'title' => 'New Event',
                'starts_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated();
    }

    // ─── Promotion creation blocked on frozen venue ───────────────────────────

    public function test_cannot_create_promotion_for_frozen_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $this->freezeVenue($venue);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/promotions', [
                'venue_id' => $venue->id,
                'title' => 'Happy Hour',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'recurrence_type' => 'one_time',
                'start_time' => '18:00',
                'end_time' => '21:00',
                'valid_from' => now()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_can_create_promotion_for_unfrozen_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/promotions', [
                'venue_id' => $venue->id,
                'title' => 'Happy Hour',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'recurrence_type' => 'one_time',
                'start_time' => '18:00',
                'end_time' => '21:00',
                'valid_from' => now()->toDateString(),
            ])
            ->assertCreated();
    }

    // ─── Pins discovery hides frozen venues ───────────────────────────────────

    public function test_nearby_pins_excludes_pins_from_frozen_venues(): void
    {
        $owner = $this->makeUser();
        $activeVenue = $this->makeVenueFor($owner);
        $frozenVenue = $this->makeVenueFor($owner);
        $this->freezeVenue($frozenVenue);

        $damascus = Point::makeGeodetic(33.5138, 36.2765);

        Pin::create(['venue_id' => $activeVenue->id, 'type' => 'venue', 'location' => $damascus]);
        Pin::create(['venue_id' => $frozenVenue->id, 'type' => 'venue', 'location' => $damascus]);

        $user = $this->makeUser();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby?lat=33.5138&lng=36.2765&radius=5000')
            ->assertOk();

        $venueIds = collect($response->json('data.data'))->pluck('venue_id')->all();

        $this->assertContains($activeVenue->id, $venueIds);
        $this->assertNotContains($frozenVenue->id, $venueIds);
    }

    public function test_nearby_pins_includes_all_pins_when_no_venue_is_frozen(): void
    {
        $owner = $this->makeUser();
        $venue1 = $this->makeVenueFor($owner);
        $venue2 = $this->makeVenueFor($owner);

        $damascus = Point::makeGeodetic(33.5138, 36.2765);

        Pin::create(['venue_id' => $venue1->id, 'type' => 'venue', 'location' => $damascus]);
        Pin::create(['venue_id' => $venue2->id, 'type' => 'venue', 'location' => $damascus]);

        $user = $this->makeUser();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins/nearby?lat=33.5138&lng=36.2765&radius=5000')
            ->assertOk();

        $this->assertCount(2, $response->json('data.data'));
    }

    // ─── Existing events/promotions still visible to owner after freeze ────────

    public function test_owner_can_still_view_existing_events_after_venue_is_frozen(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $event = Event::create([
            'venue_id' => $venue->id,
            'title' => 'Pre-freeze Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->freezeVenue($venue);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $event->id);
    }

    public function test_owner_can_still_list_existing_events_after_venue_is_frozen(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Pre-freeze Event',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->freezeVenue($venue);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/events')
            ->assertOk();

        $this->assertCount(1, $response->json('data.data'));
    }
}
