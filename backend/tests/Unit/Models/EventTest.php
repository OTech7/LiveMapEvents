<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeVenue(): Venue
    {
        $owner = User::create([
            'phone' => '+9639500' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
        ]);
        return Venue::create(['owner_id' => $owner->id, 'name' => 'Test Venue', 'type' => 'bar']);
    }

    private function makeEvent(Venue $venue, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Test Event',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'is_free' => true,
            'publish_status' => 'published',
        ], $overrides));
    }

    // ─── is_active accessor ───────────────────────────────────────────────────

    public function test_is_active_true_when_published_and_currently_in_progress(): void
    {
        $venue = $this->makeVenue();
        $event = $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'published',
        ]);

        $this->assertTrue($event->is_active);
    }

    public function test_is_active_false_when_event_has_not_started_yet(): void
    {
        $venue = $this->makeVenue();
        $event = $this->makeEvent($venue, [
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(4),
            'publish_status' => 'published',
        ]);

        $this->assertFalse($event->is_active);
    }

    public function test_is_active_false_when_event_has_already_ended(): void
    {
        $venue = $this->makeVenue();
        $event = $this->makeEvent($venue, [
            'starts_at' => now()->subHours(5),
            'ends_at' => now()->subHours(2),
            'publish_status' => 'published',
        ]);

        $this->assertFalse($event->is_active);
    }

    public function test_is_active_false_when_event_is_draft(): void
    {
        $venue = $this->makeVenue();
        $event = $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'draft',
        ]);

        $this->assertFalse($event->is_active);
    }

    public function test_is_active_false_when_event_is_cancelled(): void
    {
        $venue = $this->makeVenue();
        $event = $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'cancelled',
        ]);

        $this->assertFalse($event->is_active);
    }

    // ─── scopeActive ─────────────────────────────────────────────────────────

    public function test_scope_active_returns_only_in_progress_published_events(): void
    {
        $venue = $this->makeVenue();

        // Should be included
        $active = $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'published',
        ]);

        // Should be excluded — not started yet
        $this->makeEvent($venue, [
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(4),
            'publish_status' => 'published',
        ]);

        // Should be excluded — already ended
        $this->makeEvent($venue, [
            'starts_at' => now()->subHours(4),
            'ends_at' => now()->subHour(),
            'publish_status' => 'published',
        ]);

        // Should be excluded — draft
        $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'draft',
        ]);

        $results = Event::active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($active->id, $results->first()->id);
    }

    public function test_scope_active_returns_empty_when_no_events_in_progress(): void
    {
        $venue = $this->makeVenue();

        $this->makeEvent($venue, [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'publish_status' => 'published',
        ]);

        $this->assertCount(0, Event::active()->get());
    }

    // ─── is_active in API response ────────────────────────────────────────────

    public function test_is_active_included_in_event_resource_response(): void
    {
        $owner = User::create([
            'phone' => '+9639500' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'Venue', 'type' => 'bar']);

        $event = $this->makeEvent($venue, [
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'publish_status' => 'published',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_is_active_false_in_response_for_upcoming_event(): void
    {
        $owner = User::create([
            'phone' => '+9639500' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
        $venue = Venue::create(['owner_id' => $owner->id, 'name' => 'Venue', 'type' => 'bar']);

        $event = $this->makeEvent($venue, [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(3),
            'publish_status' => 'published',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }
}
