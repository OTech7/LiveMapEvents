<?php

namespace Tests\Unit\Requests\Venues;

use App\Http\Requests\Venues\UpdateVenueRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateVenueRequestTest extends TestCase
{
    use RefreshDatabase;

    private function rules(): array
    {
        return (new UpdateVenueRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    // ─── All fields are optional (sometimes) ─────────────────────────────────

    public function test_empty_payload_passes(): void
    {
        $v = $this->validate([]);
        $this->assertFalse($v->fails());
    }

    // ─── name ────────────────────────────────────────────────────────────────

    public function test_name_max_120_characters(): void
    {
        $v = $this->validate(['name' => str_repeat('x', 121)]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_can_be_updated(): void
    {
        $v = $this->validate(['name' => 'Updated Name']);
        $this->assertFalse($v->fails());
    }

    // ─── type ────────────────────────────────────────────────────────────────

    public function test_type_max_60_characters(): void
    {
        $v = $this->validate(['type' => str_repeat('t', 61)]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('type', $v->errors()->toArray());
    }

    // ─── address / city / notes ──────────────────────────────────────────────

    public function test_address_can_be_cleared_to_null(): void
    {
        $v = $this->validate(['address' => null]);
        $this->assertFalse($v->fails());
    }

    public function test_notes_max_2000_characters(): void
    {
        $v = $this->validate(['notes' => str_repeat('n', 2001)]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('notes', $v->errors()->toArray());
    }

    // ─── lat / lng ───────────────────────────────────────────────────────────

    public function test_lat_out_of_range_fails(): void
    {
        $v = $this->validate(['lat' => -91, 'lng' => 0]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lng_out_of_range_fails(): void
    {
        $v = $this->validate(['lat' => 0, 'lng' => 200]);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_valid_coordinates_pass(): void
    {
        $v = $this->validate(['lat' => -33.9, 'lng' => 18.4]);
        $this->assertFalse($v->fails());
    }

    // ─── combined ────────────────────────────────────────────────────────────

    public function test_partial_update_with_valid_fields_passes(): void
    {
        $v = $this->validate([
            'name' => 'New Name',
            'notes' => 'Updated notes.',
            'city' => 'Homs',
        ]);

        $this->assertFalse($v->fails());
    }
}
