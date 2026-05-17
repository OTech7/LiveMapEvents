<?php

namespace Tests\Unit\Requests\Promotions;

use App\Http\Requests\Promotions\NearbyPromotionsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NearbyPromotionsRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new NearbyPromotionsRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    public function test_lat_is_required(): void
    {
        $v = $this->validate(['lng' => 36.3]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lng_is_required(): void
    {
        $v = $this->validate(['lat' => 33.5]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_above_90(): void
    {
        $v = $this->validate(['lat' => 91, 'lng' => 36.3]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_below_minus_90(): void
    {
        $v = $this->validate(['lat' => -91, 'lng' => 36.3]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_above_180(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 181]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_below_minus_180(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => -181]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_radius_is_optional(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 36.3]);

        $this->assertFalse($v->fails());
    }

    public function test_radius_rejects_value_below_100(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 36.3, 'radius' => 50]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_radius_rejects_value_above_50000(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 36.3, 'radius' => 60000]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('radius', $v->errors()->toArray());
    }

    public function test_valid_payload_with_all_fields_passes(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 36.3, 'radius' => 5000]);

        $this->assertFalse($v->fails());
    }
}
