<?php

namespace Tests\Unit\Services;

use App\Exceptions\ApiException;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventServiceTest extends TestCase
{
    use RefreshDatabase;

    private EventService $service;
    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EventService::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639300' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
        ]);
    }

    private function makeVenueFor(User $owner): Venue
    {
        return Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Service Test Venue',
            'type' => 'bar',
        ]);
    }

    private function baseEventData(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Service Test Event',
            'starts_at' => now()->addDay()->toIso8601String(),
        ];
    }

    private function makePersistedEvent(Venue $venue, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Persisted Event',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'is_free' => true,
            'publish_status' => 'published',
        ], $overrides));
    }

    // ─── getForOwner ──────────────────────────────────────────────────────────

    public function test_get_for_owner_returns_only_owners_events(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $myVenue = $this->makeVenueFor($owner);
        $otherVenue = $this->makeVenueFor($other);

        $this->makePersistedEvent($myVenue, ['title' => 'My Event']);
        $this->makePersistedEvent($otherVenue, ['title' => 'Other Event']);

        $result = $this->service->getForOwner($owner);

        $this->assertCount(1, $result);
        $this->assertEquals('My Event', $result->first()->title);
    }

    public function test_get_for_owner_filters_by_venue_id(): void
    {
        $owner = $this->makeUser();
        $venue1 = $this->makeVenueFor($owner);
        $venue2 = $this->makeVenueFor($owner);

        $this->makePersistedEvent($venue1, ['title' => 'V1']);
        $this->makePersistedEvent($venue2, ['title' => 'V2']);

        $result = $this->service->getForOwner($owner, $venue1->id);

        $this->assertCount(1, $result);
        $this->assertEquals('V1', $result->first()->title);
    }

    public function test_get_for_owner_returns_empty_when_no_events(): void
    {
        $user = $this->makeUser();

        $result = $this->service->getForOwner($user);

        $this->assertCount(0, $result);
    }

    // ─── create ───────────────────────────────────────────────────────────────

    public function test_create_persists_event_with_correct_fields(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $data = $this->baseEventData($venue->id);

        $event = $this->service->create($owner, $data);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'venue_id' => $venue->id,
            'title' => 'Service Test Event',
        ]);
    }

    public function test_create_defaults_ends_at_to_3_hours_after_starts_at(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $startsAt = now()->addDays(2)->startOfHour();
        $data = array_merge($this->baseEventData($venue->id), [
            'starts_at' => $startsAt->toIso8601String(),
        ]);

        $event = $this->service->create($owner, $data);

        $this->assertEquals(
            $startsAt->addHours(3)->toDateTimeString(),
            $event->ends_at->toDateTimeString()
        );
    }

    public function test_create_respects_explicit_ends_at(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $starts = now()->addDays(2)->startOfHour();
        $ends = $starts->copy()->addHours(5);

        $event = $this->service->create($owner, array_merge(
            $this->baseEventData($venue->id),
            ['starts_at' => $starts->toIso8601String(), 'ends_at' => $ends->toIso8601String()]
        ));

        $this->assertEquals(
            $ends->toDateTimeString(),
            $event->ends_at->toDateTimeString()
        );
    }

    public function test_create_always_sets_is_free_to_true(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $event = $this->service->create($owner, $this->baseEventData($venue->id));

        $this->assertTrue($event->is_free);
    }

    public function test_create_defaults_publish_status_to_published(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $event = $this->service->create($owner, $this->baseEventData($venue->id));

        $this->assertEquals('published', $event->publish_status);
    }

    public function test_create_throws_when_venue_does_not_belong_to_user(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $otherVenue = $this->makeVenueFor($other);

        $this->expectException(ApiException::class);

        $this->service->create($owner, $this->baseEventData($otherVenue->id));
    }

    public function test_create_throws_when_venue_does_not_exist(): void
    {
        $owner = $this->makeUser();

        $this->expectException(ApiException::class);

        $this->service->create($owner, array_merge(
            $this->baseEventData(99999),
            ['venue_id' => 99999]
        ));
    }

    public function test_create_stores_online_event_fields(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $event = $this->service->create($owner, array_merge(
            $this->baseEventData($venue->id),
            [
                'is_online_event' => true,
                'online_event_url' => 'https://zoom.us/j/abc123',
            ]
        ));

        $this->assertTrue($event->is_online_event);
        $this->assertEquals('https://zoom.us/j/abc123', $event->online_event_url);
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_update_changes_title(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue);

        $updated = $this->service->update($event, ['title' => 'Updated Title']);

        $this->assertEquals('Updated Title', $updated->title);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_update_recalculates_ends_at_when_only_starts_at_changes(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue);
        $newStart = now()->addDays(5)->startOfHour();

        $updated = $this->service->update($event, [
            'starts_at' => $newStart->toIso8601String(),
            // ends_at intentionally omitted
        ]);

        $this->assertEquals(
            $newStart->addHours(3)->toDateTimeString(),
            $updated->ends_at->toDateTimeString()
        );
    }

    public function test_update_can_clear_rsvp_limit_to_null(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue, ['rsvp_limit' => 50]);

        $updated = $this->service->update($event, ['rsvp_limit' => null]);

        $this->assertNull($updated->rsvp_limit);
    }

    public function test_update_changes_publish_status(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue, ['publish_status' => 'published']);

        $updated = $this->service->update($event, ['publish_status' => 'draft']);

        $this->assertEquals('draft', $updated->publish_status);
    }

    // ─── cancel ───────────────────────────────────────────────────────────────

    public function test_cancel_sets_status_to_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue);

        $cancelled = $this->service->cancel($event);

        $this->assertEquals('cancelled', $cancelled->publish_status);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'publish_status' => 'cancelled',
        ]);
    }

    public function test_cancel_appends_reason_to_description(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue, ['description' => 'Original desc.']);

        $this->service->cancel($event, 'Bad weather');

        $fresh = $event->fresh();
        $this->assertStringContainsString('Original desc.', $fresh->description);
        $this->assertStringContainsString('Bad weather', $fresh->description);
    }

    public function test_cancel_throws_api_exception_when_already_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue, ['publish_status' => 'cancelled']);

        $this->expectException(ApiException::class);

        $this->service->cancel($event);
    }

    // ─── delete ───────────────────────────────────────────────────────────────

    public function test_delete_removes_event_from_database(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makePersistedEvent($venue);
        $id = $event->id;

        $this->service->delete($event);

        $this->assertDatabaseMissing('events', ['id' => $id]);
    }
}
