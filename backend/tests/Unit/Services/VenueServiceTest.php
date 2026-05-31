<?php

namespace Tests\Unit\Services;

use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\VenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenueServiceTest extends TestCase
{
    use RefreshDatabase;

    private VenueService $service;
    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VenueService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639400' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
        ]);
    }

    private function makeVenueFor(User $owner, array $overrides = []): Venue
    {
        return Venue::create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Service Test Venue',
            'type' => 'bar',
        ], $overrides));
    }

    private function baseCreateData(): array
    {
        return [
            'name' => 'New Venue',
            'type' => 'cafe',
        ];
    }

    // ─── getForOwner ─────────────────────────────────────────────────────────

    public function test_get_for_owner_returns_only_owners_venues(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();

        $this->makeVenueFor($owner, ['name' => 'A']);
        $this->makeVenueFor($owner, ['name' => 'B']);
        $this->makeVenueFor($other, ['name' => 'C']);

        $result = $this->service->getForOwner($owner);

        $this->assertCount(2, $result);
        $names = $result->pluck('name')->sort()->values()->all();
        $this->assertEquals(['A', 'B'], $names);
    }

    public function test_get_for_owner_returns_empty_when_no_venues(): void
    {
        $owner = $this->makeUser();
        $result = $this->service->getForOwner($owner);

        $this->assertCount(0, $result);
    }

    // ─── create ──────────────────────────────────────────────────────────────

    public function test_create_persists_venue_with_correct_owner(): void
    {
        $owner = $this->makeUser();
        $venue = $this->service->create($owner, $this->baseCreateData());

        $this->assertInstanceOf(Venue::class, $venue);
        $this->assertEquals($owner->id, $venue->owner_id);
        $this->assertEquals('New Venue', $venue->name);
        $this->assertEquals('cafe', $venue->type);
        $this->assertFalse($venue->isFrozen());
    }

    public function test_create_saves_optional_fields(): void
    {
        $owner = $this->makeUser();
        $venue = $this->service->create($owner, array_merge($this->baseCreateData(), [
            'address' => '10 Test St',
            'city' => 'Damascus',
            'notes' => 'Back entrance only.',
        ]));

        $this->assertEquals('10 Test St', $venue->address);
        $this->assertEquals('Damascus', $venue->city);
        $this->assertEquals('Back entrance only.', $venue->notes);
    }

    public function test_create_stores_geo_coordinates(): void
    {
        $owner = $this->makeUser();
        $venue = $this->service->create($owner, array_merge($this->baseCreateData(), [
            'lat' => 33.5138,
            'lng' => 36.2765,
        ]));

        $this->assertNotNull($venue->location);
        $this->assertEqualsWithDelta(33.5138, $venue->location->getLatitude(), 0.0001);
        $this->assertEqualsWithDelta(36.2765, $venue->location->getLongitude(), 0.0001);
    }

    public function test_create_without_coordinates_stores_null_location(): void
    {
        $owner = $this->makeUser();
        $venue = $this->service->create($owner, $this->baseCreateData());

        $this->assertNull($venue->location);
    }

    // ─── update ──────────────────────────────────────────────────────────────

    public function test_update_changes_name_and_type(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner, ['name' => 'Old', 'type' => 'bar']);

        $updated = $this->service->update($venue, ['name' => 'New', 'type' => 'club']);

        $this->assertEquals('New', $updated->name);
        $this->assertEquals('club', $updated->type);
        $this->assertDatabaseHas('venues', ['id' => $venue->id, 'name' => 'New', 'type' => 'club']);
    }

    public function test_update_can_set_notes(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $updated = $this->service->update($venue, ['notes' => 'Updated notes.']);

        $this->assertEquals('Updated notes.', $updated->notes);
    }

    public function test_update_partial_fields_does_not_wipe_others(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner, ['name' => 'Original', 'city' => 'Damascus']);

        // Only update city
        $updated = $this->service->update($venue, ['city' => 'Aleppo']);

        $this->assertEquals('Original', $updated->name);
        $this->assertEquals('Aleppo', $updated->city);
    }

    // ─── delete ──────────────────────────────────────────────────────────────

    public function test_delete_removes_venue_from_database(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->service->delete($venue);

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_delete_throws_when_venue_has_upcoming_published_events(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Upcoming',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(3),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->expectException(ApiException::class);
        $this->service->delete($venue);
    }

    public function test_delete_succeeds_when_all_events_are_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Cancelled',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(3),
            'is_free' => true,
            'publish_status' => 'cancelled',
        ]);

        $this->service->delete($venue);

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_delete_succeeds_when_events_are_in_the_past(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Past Event',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(5)->addHours(3),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->service->delete($venue);

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }
}
