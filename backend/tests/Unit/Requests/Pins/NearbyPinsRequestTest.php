<?php

namespace Tests\Unit\Requests\Pins;

use App\Http\Requests\Pins\NearbyPinsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NearbyPinsRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new NearbyPinsRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    private function validPayload(): array
    {
        return ['lat' => 33.5, 'lng' => 36.3, 'radius' => 500];
    }

    // ─── lat ──────────────────────────────────────────────────────────────────

    public function test_lat_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['lat']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_above_90(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => 91]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_below_minus_90(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lat' => -91]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_accepts_boundary_values(): void
    {
        foreach ([90, -90, 0] as $val) {
            $v = $this->validate(array_merge($this->validPayload(), ['lat' => $val]));
            $this->assertFalse($v->fails(), "lat=$val should pass");
        }
    }

    // ─── lng ──────────────────────────────────────────────────────────────────

    public function test_lng_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['lng']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_above_180(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lng' => 181]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_below_minus_180(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['lng' => -181]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    // ─── radius ───────────────────────────────────────────────────────────────

    public function test_radius_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['radius']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_rejects_value_below_100(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['radius' => 99]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_rejects_value_above_50000(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['radius' => 50001]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_accepts_boundary_values(): void
    {
        foreach ([100, 50000] as $val) {
            $v = $this->validate(array_merge($this->validPayload(), ['radius' => $val]));
            $this->assertFalse($v->fails(), "radius=$val should pass");
        }
    }

    // ─── optional fields ──────────────────────────────────────────────────────

    public function test_types_is_optional(): void
    {
        $v = $this->validate($this->validPayload());

        $this->assertFalse($v->fails());
    }

    public function test_types_must_be_an_array_when_provided(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['types' => 'restaurant']));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('types', $v->errors()->toArray());
    }

    public function test_categories_must_be_an_array_of_integers_when_provided(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['categories' => ['abc']]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('categories.0', $v->errors()->toArray());
    }

    // ─── full valid payload ───────────────────────────────────────────────────

    public function test_complete_valid_payload_passes(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), [
            'types' => ['restaurant', 'bar'],
            'categories' => [1, 2, 3],
        ]));

        $this->assertFalse($v->fails());
    }
}
