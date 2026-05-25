<?php

namespace Tests\Unit\Requests\Events;

use App\Http\Requests\Events\StoreEventRequest;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreEventRequestTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function rules(): array
    {
        return (new StoreEventRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    private function makeVenue(): Venue
    {
        $owner = User::create([
            'phone' => '+9639400' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
        ]);
        return Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
    }

    private function validPayload(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Test Event',
            'starts_at' => now()->addDay()->toIso8601String(),
        ];
    }

    // ─── venue_id ─────────────────────────────────────────────────────────────

    public function test_venue_id_is_required(): void
    {
        $data = $this->validPayload(1);
        unset($data['venue_id']);

        $v = $this->validate($data);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('venue_id', $v->errors()->toArray());
    }

    public function test_venue_id_must_exist_in_venues_table(): void
    {
        $v = $this->validate($this->validPayload(99999));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('venue_id', $v->errors()->toArray());
    }

    public function test_venue_id_passes_when_venue_exists(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate($this->validPayload($venue->id));

        $this->assertFalse($v->fails());
    }

    // ─── title ────────────────────────────────────────────────────────────────

    public function test_title_is_required(): void
    {
        $venue = $this->makeVenue();
        $data = $this->validPayload($venue->id);
        unset($data['title']);

        $v = $this->validate($data);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('title', $v->errors()->toArray());
    }

    public function test_title_cannot_exceed_80_characters(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['title' => str_repeat('a', 81)]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('title', $v->errors()->toArray());
    }

    public function test_title_of_exactly_80_characters_passes(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['title' => str_repeat('a', 80)]
        ));

        $this->assertFalse($v->fails());
    }

    // ─── starts_at ────────────────────────────────────────────────────────────

    public function test_starts_at_is_required(): void
    {
        $venue = $this->makeVenue();
        $data = $this->validPayload($venue->id);
        unset($data['starts_at']);

        $v = $this->validate($data);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('starts_at', $v->errors()->toArray());
    }

    public function test_starts_at_must_not_be_in_the_past(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['starts_at' => now()->subHour()->toIso8601String()]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('starts_at', $v->errors()->toArray());
    }

    public function test_starts_at_must_be_a_valid_date(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['starts_at' => 'not-a-date']
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('starts_at', $v->errors()->toArray());
    }

    // ─── ends_at ──────────────────────────────────────────────────────────────

    public function test_ends_at_is_optional(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate($this->validPayload($venue->id)); // no ends_at

        $this->assertFalse($v->fails());
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $venue = $this->makeVenue();
        $start = now()->addDay();

        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            [
                'starts_at' => $start->toIso8601String(),
                'ends_at' => $start->copy()->subHour()->toIso8601String(),
            ]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('ends_at', $v->errors()->toArray());
    }

    // ─── online event ─────────────────────────────────────────────────────────

    public function test_online_event_url_is_required_when_is_online_event_is_true(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['is_online_event' => true]
        // online_event_url absent
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('online_event_url', $v->errors()->toArray());
    }

    public function test_online_event_url_must_be_a_valid_url(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            [
                'is_online_event' => true,
                'online_event_url' => 'not-a-url',
            ]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('online_event_url', $v->errors()->toArray());
    }

    public function test_online_event_url_not_required_when_is_online_event_is_false(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['is_online_event' => false]
        ));

        $this->assertFalse($v->fails());
    }

    public function test_online_event_with_valid_url_passes(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            [
                'is_online_event' => true,
                'online_event_url' => 'https://meet.google.com/abc-def-ghi',
            ]
        ));

        $this->assertFalse($v->fails());
    }

    // ─── rsvp_limit ───────────────────────────────────────────────────────────

    public function test_rsvp_limit_must_be_at_least_1_when_provided(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['rsvp_limit' => 0]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('rsvp_limit', $v->errors()->toArray());
    }

    public function test_rsvp_limit_is_optional(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate($this->validPayload($venue->id));

        $this->assertFalse($v->fails());
    }

    // ─── guest_limit ──────────────────────────────────────────────────────────

    public function test_guest_limit_cannot_exceed_10(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['guest_limit' => 11]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('guest_limit', $v->errors()->toArray());
    }

    public function test_guest_limit_of_0_is_valid(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['guest_limit' => 0]
        ));

        $this->assertFalse($v->fails());
    }

    // ─── publish_status ───────────────────────────────────────────────────────

    public function test_publish_status_must_be_published_or_draft(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['publish_status' => 'live']
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('publish_status', $v->errors()->toArray());
    }

    public function test_publish_status_accepts_published(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['publish_status' => 'published']
        ));

        $this->assertFalse($v->fails());
    }

    public function test_publish_status_accepts_draft(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['publish_status' => 'draft']
        ));

        $this->assertFalse($v->fails());
    }

    // ─── full valid payload ───────────────────────────────────────────────────

    public function test_complete_payload_passes_all_rules(): void
    {
        $venue = $this->makeVenue();
        $start = now()->addDays(3);

        $v = $this->validate([
            'venue_id' => $venue->id,
            'title' => 'Tech Meetup 2026',
            'description' => 'Learn about AI.',
            'category' => 'Technology',
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $start->copy()->addHours(4)->toIso8601String(),
            'is_online_event' => true,
            'online_event_url' => 'https://zoom.us/j/123456',
            'rsvp_limit' => 100,
            'guest_limit' => 2,
            'publish_status' => 'published',
        ]);

        $this->assertFalse($v->fails());
    }
}
