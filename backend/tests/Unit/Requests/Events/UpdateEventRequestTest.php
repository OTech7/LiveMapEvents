<?php

namespace Tests\Unit\Requests\Events;

use App\Http\Requests\Events\UpdateEventRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateEventRequestTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function rules(): array
    {
        return (new UpdateEventRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    // ─── Empty payload (all fields optional) ─────────────────────────────────

    public function test_empty_payload_passes_because_all_fields_are_optional(): void
    {
        $v = $this->validate([]);

        $this->assertFalse($v->fails());
    }

    // ─── title ────────────────────────────────────────────────────────────────

    public function test_title_cannot_exceed_80_characters_when_provided(): void
    {
        $v = $this->validate(['title' => str_repeat('x', 81)]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('title', $v->errors()->toArray());
    }

    public function test_title_of_80_characters_passes(): void
    {
        $v = $this->validate(['title' => str_repeat('x', 80)]);

        $this->assertFalse($v->fails());
    }

    // ─── starts_at / ends_at ─────────────────────────────────────────────────

    public function test_starts_at_cannot_be_in_the_past(): void
    {
        $v = $this->validate(['starts_at' => now()->subHour()->toIso8601String()]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('starts_at', $v->errors()->toArray());
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $start = now()->addDay();

        $v = $this->validate([
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $start->copy()->subHour()->toIso8601String(),
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('ends_at', $v->errors()->toArray());
    }

    public function test_ends_at_after_starts_at_passes(): void
    {
        $start = now()->addDay();

        $v = $this->validate([
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $start->copy()->addHours(2)->toIso8601String(),
        ]);

        $this->assertFalse($v->fails());
    }

    // ─── online event ─────────────────────────────────────────────────────────

    public function test_online_event_url_is_required_when_is_online_event_sent_as_true(): void
    {
        $v = $this->validate([
            'is_online_event' => true,
            // online_event_url omitted
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('online_event_url', $v->errors()->toArray());
    }

    public function test_online_event_url_must_be_valid_url(): void
    {
        $v = $this->validate([
            'is_online_event' => true,
            'online_event_url' => 'just-text',
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('online_event_url', $v->errors()->toArray());
    }

    public function test_online_event_update_with_valid_url_passes(): void
    {
        $v = $this->validate([
            'is_online_event' => true,
            'online_event_url' => 'https://zoom.us/j/987654',
        ]);

        $this->assertFalse($v->fails());
    }

    // ─── publish_status ───────────────────────────────────────────────────────

    public function test_publish_status_rejects_invalid_value(): void
    {
        $v = $this->validate(['publish_status' => 'cancelled']); // not allowed via update

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('publish_status', $v->errors()->toArray());
    }

    public function test_publish_status_accepts_draft_and_published(): void
    {
        $this->assertFalse($this->validate(['publish_status' => 'draft'])->fails());
        $this->assertFalse($this->validate(['publish_status' => 'published'])->fails());
    }

    // ─── rsvp_limit / guest_limit ─────────────────────────────────────────────

    public function test_rsvp_limit_must_be_at_least_1_when_provided(): void
    {
        $v = $this->validate(['rsvp_limit' => 0]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('rsvp_limit', $v->errors()->toArray());
    }

    public function test_guest_limit_cannot_exceed_10(): void
    {
        $v = $this->validate(['guest_limit' => 11]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('guest_limit', $v->errors()->toArray());
    }

    // ─── full valid partial payload ───────────────────────────────────────────

    public function test_partial_valid_payload_passes(): void
    {
        $start = now()->addDays(3);

        $v = $this->validate([
            'title' => 'Updated Title',
            'description' => 'Updated description.',
            'publish_status' => 'draft',
            'rsvp_limit' => 50,
            'guest_limit' => 1,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $start->copy()->addHours(2)->toIso8601String(),
        ]);

        $this->assertFalse($v->fails());
    }
}
