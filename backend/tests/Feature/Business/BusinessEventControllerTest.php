<?php

namespace Tests\Feature\Business;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639200' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makeVenueFor(User $owner): Venue
    {
        return Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Test Venue',
            'type' => 'bar',
        ]);
    }

    private function makeEvent(Venue $venue, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'venue_id' => $venue->id,
            'title' => 'Test Event',
            'description' => 'A great event.',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(3)->toDateTimeString(),
            'is_free' => true,
            'publish_status' => 'published',
        ], $overrides));
    }

    /** Minimal valid payload for POST /business/events */
    private function validStorePayload(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Monthly Meetup',
            'starts_at' => now()->addDays(2)->toIso8601String(),
        ];
    }

    // ─── Authentication guard ─────────────────────────────────────────────────

    public function test_all_event_routes_require_authentication(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);
        $event = $this->makeEvent($venue);

        $this->getJson('/api/v1/business/events')->assertUnauthorized();
        $this->postJson('/api/v1/business/events', [])->assertUnauthorized();
        $this->getJson("/api/v1/business/events/{$event->id}")->assertUnauthorized();
        $this->putJson("/api/v1/business/events/{$event->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/business/events/{$event->id}")->assertUnauthorized();
        $this->postJson("/api/v1/business/events/{$event->id}/cancel")->assertUnauthorized();
    }

    // ─── GET /business/events ─────────────────────────────────────────────────

    public function test_index_returns_only_owners_events(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $myVenue = $this->makeVenueFor($owner);
        $otherVenue = $this->makeVenueFor($other);

        $this->makeEvent($myVenue, ['title' => 'My Event']);
        $this->makeEvent($otherVenue, ['title' => 'Not Mine']);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/events')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', 'My Event');
    }

    public function test_index_filters_by_venue_id(): void
    {
        $owner = $this->makeUser();
        $venue1 = $this->makeVenueFor($owner);
        $venue2 = $this->makeVenueFor($owner);

        $this->makeEvent($venue1, ['title' => 'Venue 1 Event']);
        $this->makeEvent($venue2, ['title' => 'Venue 2 Event']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/events?venue_id={$venue1->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', 'Venue 1 Event');
    }

    public function test_index_returns_empty_when_owner_has_no_events(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/events')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');
    }

    // ─── POST /business/events ────────────────────────────────────────────────

    public function test_store_creates_event_for_owned_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $this->validStorePayload($venue->id))
            ->assertCreated()
            ->assertJsonPath('data.title', 'Monthly Meetup')
            ->assertJsonPath('data.publish_status', 'published');

        $this->assertDatabaseHas('events', [
            'venue_id' => $venue->id,
            'title' => 'Monthly Meetup',
        ]);
    }

    public function test_store_defaults_ends_at_to_3_hours_after_starts_at(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $payload = $this->validStorePayload($venue->id);
        $payload['starts_at'] = now()->addDays(3)->startOfHour()->toIso8601String();
        unset($payload['ends_at']);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertCreated();

        $event = Event::latest('id')->first();
        $this->assertEquals(
            $event->starts_at->addHours(3)->toDateTimeString(),
            $event->ends_at->toDateTimeString()
        );
    }

    public function test_store_saves_draft_status_when_requested(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $payload = $this->validStorePayload($venue->id);
        $payload['publish_status'] = 'draft';

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.publish_status', 'draft');
    }

    public function test_store_returns_422_when_title_is_missing(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $payload = $this->validStorePayload($venue->id);
        unset($payload['title']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_starts_at_is_missing(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $payload = $this->validStorePayload($venue->id);
        unset($payload['starts_at']);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_store_returns_422_for_online_event_without_url(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $payload = array_merge($this->validStorePayload($venue->id), [
            'is_online_event' => true,
            // online_event_url intentionally omitted
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['online_event_url']);
    }

    public function test_store_accepts_online_event_with_valid_url(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $payload = array_merge($this->validStorePayload($venue->id), [
            'is_online_event' => true,
            'online_event_url' => 'https://meet.google.com/abc-def',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $payload)
            ->assertCreated()
            ->assertJsonPath('data.is_online_event', true);
    }

    public function test_store_returns_403_when_venue_belongs_to_another_user(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $otherVenue = $this->makeVenueFor($other);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', $this->validStorePayload($otherVenue->id))
            ->assertForbidden();
    }

    // ─── GET /business/events/{event} ─────────────────────────────────────────

    public function test_show_returns_event_with_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue, ['title' => 'Big Conference']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Big Conference')
            ->assertJsonPath('data.venue.id', $venue->id);
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/business/events/{$event->id}")
            ->assertForbidden();
    }

    public function test_show_returns_404_for_nonexistent_event(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/events/99999')
            ->assertNotFound();
    }

    // ─── PUT /business/events/{event} ─────────────────────────────────────────

    public function test_update_changes_event_title(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/events/{$event->id}", ['title' => 'New Title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'New Title']);
    }

    public function test_update_changes_publish_status_to_draft(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue, ['publish_status' => 'published']);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/events/{$event->id}", ['publish_status' => 'draft'])
            ->assertOk()
            ->assertJsonPath('data.publish_status', 'draft');
    }

    public function test_update_can_clear_rsvp_limit(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue, ['rsvp_limit' => 50]);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/events/{$event->id}", ['rsvp_limit' => null])
            ->assertOk()
            ->assertJsonPath('data.rsvp_limit', null);
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/business/events/{$event->id}", ['title' => 'Hacked'])
            ->assertForbidden();
    }

    // ─── DELETE /business/events/{event} ─────────────────────────────────────

    public function test_destroy_deletes_event_from_database(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/events/{$event->id}")
            ->assertOk();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/business/events/{$event->id}")
            ->assertForbidden();
    }

    // ─── POST /business/events/{event}/cancel ─────────────────────────────────

    public function test_cancel_sets_publish_status_to_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/events/{$event->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.publish_status', 'cancelled');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'publish_status' => 'cancelled',
        ]);
    }

    public function test_cancel_appends_reason_to_description(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue, ['description' => 'Great event.']);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/events/{$event->id}/cancel", [
                'reason' => 'Venue flooding',
            ])
            ->assertOk();

        $this->assertStringContainsString(
            'Venue flooding',
            Event::find($event->id)->description
        );
    }

    public function test_cancel_returns_409_when_event_already_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue, ['publish_status' => 'cancelled']);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/events/{$event->id}/cancel")
            ->assertStatus(409);
    }

    public function test_cancel_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);
        $event = $this->makeEvent($venue);

        $this->actingAs($stranger, 'sanctum')
            ->postJson("/api/v1/business/events/{$event->id}/cancel")
            ->assertForbidden();
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    public function test_store_response_contains_expected_fields(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/events', array_merge(
                $this->validStorePayload($venue->id),
                ['description' => 'Details here', 'rsvp_limit' => 30]
            ))
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'venue',
                    'title',
                    'description',
                    'starts_at',
                    'ends_at',
                    'is_online_event',
                    'is_free',
                    'rsvp_limit',
                    'guest_limit',
                    'publish_status',
                    'created_at',
                ],
            ]);
    }
}
