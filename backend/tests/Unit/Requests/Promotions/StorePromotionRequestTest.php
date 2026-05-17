<?php

namespace Tests\Unit\Requests\Promotions;

use App\Http\Requests\Promotions\StorePromotionRequest;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePromotionRequestTest extends TestCase
{
    use RefreshDatabase;

    private function rules(): array
    {
        return (new StorePromotionRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    private function makeVenue(): Venue
    {
        $owner = User::create(['phone' => '+963911000001']);
        return Venue::create(['owner_id' => $owner->id, 'name' => 'V', 'type' => 'bar']);
    }

    private function validPayload(int $venueId): array
    {
        return [
            'venue_id' => $venueId,
            'title' => 'Happy Hour',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'recurrence_type' => 'one_time',
            'start_time' => '18:00:00',
            'end_time' => '21:00:00',
            'valid_from' => now()->toDateString(),
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

    public function test_title_cannot_exceed_120_characters(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['title' => str_repeat('a', 121)]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('title', $v->errors()->toArray());
    }

    // ─── discount_type ────────────────────────────────────────────────────────

    public function test_discount_type_must_be_percentage_or_fixed(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['discount_type' => 'crypto']
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('discount_type', $v->errors()->toArray());
    }

    // ─── discount_value ───────────────────────────────────────────────────────

    public function test_discount_value_must_be_at_least_0_01(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['discount_value' => 0]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('discount_value', $v->errors()->toArray());
    }

    // ─── time validation ──────────────────────────────────────────────────────

    public function test_end_time_must_be_after_start_time(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['start_time' => '21:00:00', 'end_time' => '18:00:00']
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('end_time', $v->errors()->toArray());
    }

    public function test_start_time_and_end_time_accept_H_colon_i_format(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['start_time' => '09:00', 'end_time' => '21:00']
        ));

        $this->assertFalse($v->fails());
    }

    // ─── recurrence / days_of_week ────────────────────────────────────────────

    public function test_days_of_week_is_required_for_recurring_type(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['recurrence_type' => 'recurring']
        // days_of_week intentionally absent
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('days_of_week', $v->errors()->toArray());
    }

    public function test_days_of_week_not_required_for_one_time_type(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['recurrence_type' => 'one_time']
        ));

        $this->assertFalse($v->fails());
    }

    public function test_days_of_week_values_must_be_between_1_and_7(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['recurrence_type' => 'recurring', 'days_of_week' => [0, 8]]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('days_of_week.0', $v->errors()->toArray());
        $this->assertArrayHasKey('days_of_week.1', $v->errors()->toArray());
    }

    public function test_days_of_week_accepts_values_1_through_7(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['recurrence_type' => 'recurring', 'days_of_week' => [1, 3, 5]]
        ));

        $this->assertFalse($v->fails());
    }

    // ─── valid_from / valid_to ────────────────────────────────────────────────

    public function test_valid_from_cannot_be_in_the_past(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            ['valid_from' => now()->subDay()->toDateString()]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('valid_from', $v->errors()->toArray());
    }

    public function test_valid_to_must_be_on_or_after_valid_from(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate(array_merge(
            $this->validPayload($venue->id),
            [
                'valid_from' => now()->addDays(5)->toDateString(),
                'valid_to' => now()->addDays(2)->toDateString(),
            ]
        ));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('valid_to', $v->errors()->toArray());
    }

    public function test_valid_to_is_optional(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate($this->validPayload($venue->id));

        $this->assertFalse($v->fails());
    }

    // ─── full valid payload ───────────────────────────────────────────────────

    public function test_complete_valid_payload_passes_all_rules(): void
    {
        $venue = $this->makeVenue();
        $v = $this->validate($this->validPayload($venue->id));

        $this->assertFalse($v->fails());
    }
}
