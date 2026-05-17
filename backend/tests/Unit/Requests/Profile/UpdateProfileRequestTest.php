<?php

namespace Tests\Unit\Requests\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new UpdateProfileRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    public function test_empty_payload_passes_because_all_fields_are_sometimes(): void
    {
        $v = $this->validate([]);

        $this->assertFalse($v->fails());
    }

    public function test_gender_must_be_male_or_female_when_provided(): void
    {
        $v = $this->validate(['gender' => 'other']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('gender', $v->errors()->toArray());
    }

    public function test_gender_male_is_valid(): void
    {
        $v = $this->validate(['gender' => 'male']);

        $this->assertFalse($v->fails());
    }

    public function test_gender_female_is_valid(): void
    {
        $v = $this->validate(['gender' => 'female']);

        $this->assertFalse($v->fails());
    }

    public function test_first_name_cannot_exceed_255_characters(): void
    {
        $v = $this->validate(['first_name' => str_repeat('a', 256)]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_last_name_cannot_exceed_255_characters(): void
    {
        $v = $this->validate(['last_name' => str_repeat('z', 256)]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('last_name', $v->errors()->toArray());
    }

    public function test_lat_must_be_numeric_when_provided(): void
    {
        $v = $this->validate(['lat' => 'not-a-number']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_above_90(): void
    {
        $v = $this->validate(['lat' => 91]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lat_rejects_value_below_minus_90(): void
    {
        $v = $this->validate(['lat' => -91]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lat', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_above_180(): void
    {
        $v = $this->validate(['lng' => 185]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_lng_rejects_value_below_minus_180(): void
    {
        $v = $this->validate(['lng' => -185]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('lng', $v->errors()->toArray());
    }

    public function test_valid_coordinates_pass(): void
    {
        $v = $this->validate(['lat' => 33.5, 'lng' => 36.3]);

        $this->assertFalse($v->fails());
    }

    public function test_dob_must_be_a_date_when_provided(): void
    {
        $v = $this->validate(['dob' => 'not-a-date']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('dob', $v->errors()->toArray());
    }
}
