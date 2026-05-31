<?php

namespace Tests\Feature\Business;

use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessVenueControllerTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(): User
    {
        return User::create([
            'phone' => '+9639300' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'profile_complete' => true,
        ]);
    }

    private function makeVenueFor(User $owner, array $overrides = []): Venue
    {
        return Venue::create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Test Venue',
            'type' => 'bar',
            'address' => '123 Main St',
            'city' => 'Damascus',
        ], $overrides));
    }

    private function validStorePayload(): array
    {
        return [
            'name' => 'My New Venue',
            'type' => 'cafe',
            'address' => '456 Side St',
            'city' => 'Aleppo',
        ];
    }

    // ─── Auth guard ───────────────────────────────────────────────────────────

    public function test_all_venue_routes_require_authentication(): void
    {
        $user = $this->makeUser();
        $venue = $this->makeVenueFor($user);

        $this->getJson('/api/v1/business/venues')->assertUnauthorized();
        $this->postJson('/api/v1/business/venues', [])->assertUnauthorized();
        $this->getJson("/api/v1/business/venues/{$venue->id}")->assertUnauthorized();
        $this->putJson("/api/v1/business/venues/{$venue->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/business/venues/{$venue->id}")->assertUnauthorized();
    }

    // ─── GET /business/venues ─────────────────────────────────────────────────

    public function test_index_returns_only_owners_venues(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();

        $this->makeVenueFor($owner, ['name' => 'Owner Venue 1']);
        $this->makeVenueFor($owner, ['name' => 'Owner Venue 2']);
        $this->makeVenueFor($other, ['name' => 'Other Venue']);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/venues')
            ->assertOk();

        $names = collect($response->json('data.data'))->pluck('name');
        $this->assertCount(2, $names);
        $this->assertTrue($names->contains('Owner Venue 1'));
        $this->assertTrue($names->contains('Owner Venue 2'));
        $this->assertFalse($names->contains('Other Venue'));
    }

    public function test_index_returns_empty_for_owner_with_no_venues(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/venues')
            ->assertOk()
            ->assertJsonPath('data.data', []);
    }

    // ─── POST /business/venues ────────────────────────────────────────────────

    public function test_store_creates_venue_owned_by_authenticated_user(): void
    {
        $owner = $this->makeUser();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', $this->validStorePayload())
            ->assertCreated();

        $this->assertDatabaseHas('venues', [
            'owner_id' => $owner->id,
            'name' => 'My New Venue',
            'type' => 'cafe',
            'city' => 'Aleppo',
        ]);

        $this->assertEquals('My New Venue', $response->json('data.name'));
    }

    public function test_store_sets_is_frozen_false_by_default(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', $this->validStorePayload())
            ->assertCreated()
            ->assertJsonPath('data.is_frozen', false);
    }

    public function test_store_saves_optional_notes(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', array_merge(
                $this->validStorePayload(),
                ['notes' => 'Private parking available at the back.']
            ))
            ->assertCreated()
            ->assertJsonPath('data.notes', 'Private parking available at the back.');
    }

    public function test_store_saves_lat_lng_coordinates(): void
    {
        $owner = $this->makeUser();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', array_merge(
                $this->validStorePayload(),
                ['lat' => 33.5138, 'lng' => 36.2765]
            ))
            ->assertCreated();

        $this->assertNotNull($response->json('data.lat'));
        $this->assertNotNull($response->json('data.lng'));
        $this->assertEqualsWithDelta(33.5138, $response->json('data.lat'), 0.0001);
        $this->assertEqualsWithDelta(36.2765, $response->json('data.lng'), 0.0001);
    }

    public function test_store_fails_without_required_fields(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_store_fails_with_invalid_coordinates(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', array_merge(
                $this->validStorePayload(),
                ['lat' => 999, 'lng' => 999]
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    // ─── GET /business/venues/{venue} ─────────────────────────────────────────

    public function test_show_returns_venue_for_owner(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner, ['name' => 'My Bar']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $venue->id)
            ->assertJsonPath('data.name', 'My Bar');
    }

    public function test_show_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/business/venues/{$venue->id}")
            ->assertForbidden();
    }

    public function test_show_returns_404_for_unknown_venue(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/business/venues/99999')
            ->assertNotFound();
    }

    // ─── PUT /business/venues/{venue} ─────────────────────────────────────────

    public function test_update_changes_venue_fields(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner, ['name' => 'Old Name', 'city' => 'Damascus']);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/venues/{$venue->id}", [
                'name' => 'New Name',
                'city' => 'Aleppo',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.city', 'Aleppo');

        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'New Name',
            'city' => 'Aleppo',
        ]);
    }

    public function test_update_can_set_notes(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/venues/{$venue->id}", [
                'notes' => 'Updated venue notes.',
            ])
            ->assertOk()
            ->assertJsonPath('data.notes', 'Updated venue notes.');
    }

    public function test_update_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($stranger, 'sanctum')
            ->putJson("/api/v1/business/venues/{$venue->id}", ['name' => 'Hack'])
            ->assertForbidden();
    }

    public function test_update_rejects_invalid_name_length(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->putJson("/api/v1/business/venues/{$venue->id}", [
                'name' => str_repeat('x', 121),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    // ─── DELETE /business/venues/{venue} ──────────────────────────────────────

    public function test_destroy_deletes_venue(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk();

        $this->assertDatabaseMissing('venues', ['id' => $venue->id]);
    }

    public function test_destroy_returns_403_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        $this->actingAs($stranger, 'sanctum')
            ->deleteJson("/api/v1/business/venues/{$venue->id}")
            ->assertForbidden();
    }

    public function test_destroy_blocked_when_venue_has_active_events(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Upcoming Event',
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/venues/{$venue->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('venues', ['id' => $venue->id]);
    }

    public function test_destroy_allowed_when_events_are_in_past(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        // Past event — should not block deletion
        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Past Event',
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDays(5)->addHours(2),
            'is_free' => true,
            'publish_status' => 'published',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk();
    }

    public function test_destroy_allowed_when_events_are_cancelled(): void
    {
        $owner = $this->makeUser();
        $venue = $this->makeVenueFor($owner);

        Event::create([
            'venue_id' => $venue->id,
            'title' => 'Cancelled Event',
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
            'is_free' => true,
            'publish_status' => 'cancelled',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/business/venues/{$venue->id}")
            ->assertOk();
    }

    // ─── Response shape ───────────────────────────────────────────────────────

    public function test_store_response_contains_expected_fields(): void
    {
        $owner = $this->makeUser();

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/venues', array_merge(
                $this->validStorePayload(),
                ['notes' => 'Some notes', 'lat' => 33.5, 'lng' => 36.3]
            ))
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'type',
                    'address',
                    'city',
                    'notes',
                    'lat',
                    'lng',
                    'is_frozen',
                    'frozen_at',
                    'freeze_reason',
                    'is_verified',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }
}
