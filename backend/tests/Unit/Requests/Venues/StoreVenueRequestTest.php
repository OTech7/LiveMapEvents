<?php

namespace Tests\Unit\Requests\Venues;

use App\Http\Requests\Venues\StoreVenueRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreVenueRequestTest extends TestCase
{
    use RefreshDatabase;

    private function rules(): array
    {
        return (new StoreVenueRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    private function validPayload(): array
    {
        return [
            'name' => 'My Venue',
            'type' => 'bar',
        ];
    }

    // ─── name ────────────────────────────────────────────────────────────────

    public function test_name_is_required(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['name' => '']));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_max_120_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['name' => str_repeat('x', 121)]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('name', $v->errors()->toArray());
    }

    public function test_name_exactly_120_characters_passes(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['name' => str_repeat('x', 120)]));
        $this->assertFalse($v->fails());
    }

    // ─── type ────────────────────────────────────────────────────────────────

    public function test_type_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['type']);
        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('type', $v->errors()->toArray());
    }

    public function test_type_max_60_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['type' => str_repeat('t', 61)]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('type', $v->errors()->toArray());
    }

    // ─── address / city / notes ──────────────────────────────────────────────

    public function test_address_is_optional(): void
    {
        $v = $this->validate($this->validPayload());
        $this->assertFalse($v->fails());
    }

    public function test_address_max_255_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['address' => str_repeat('a', 256)]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('address', $v->errors()->toArray());
    }

    public function test_city_max_100_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['city' => str_repeat('c', 101)]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('city', $v->errors()->toArray());
    }

    public function test_notes_max_2000_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['notes' => str_repeat('n', 2001)]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('notes', $v->errors()->toArray());
    }

    // ─── lat / lng ───────────────────────────────────────────────────────────

    public function test_lat_must_be_numeric(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => 'not-a-number', 'lng' => 36.0]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_must_be_between_minus_90_and_90(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => 91, 'lng' => 36.0]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lng_must_be_between_minus_180_and_180(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => 33.0, 'lng' => 181]));
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_valid_lat_lng_passes(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => 33.5138, 'lng' => 36.2765]));
        $this->assertFalse($v->fails());
    }

    public function test_lat_lng_are_optional(): void
    {
        $v = $this->validate($this->validPayload());
        $this->assertFalse($v->fails());
    }

    // ─── full valid payload ───────────────────────────────────────────────────

    public function test_full_valid_payload_passes(): void
    {
        $v = $this->validate([
            'name' => 'Damascus Rooftop Bar',
            'type' => 'bar',
            'address' => '10 Straight Street',
            'city' => 'Damascus',
            'notes' => 'Rooftop access via elevator.',
            'lat' => 33.5138,
            'lng' => 36.2765,
        ]);

        $this->assertFalse($v->fails());
    }
}
