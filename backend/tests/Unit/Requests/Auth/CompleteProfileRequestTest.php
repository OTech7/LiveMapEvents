<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CompleteProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a validator using CompleteProfileRequest rules for a given user context.
     * $userHasPhone controls the phone prohibited/nullable branch.
     */
    private function validate(array $data, bool $userHasPhone = false): \Illuminate\Validation\Validator
    {
        // Simulate the request for a user who may or may not already have a phone
        $user = $userHasPhone
            ? User::create(['phone' => '+963911000099'])
            : User::create(['google_id' => 'gid-test']);

        // Build a request instance bound to that user so rules() can call $this->user()
        $request = CompleteProfileRequest::createFrom(
            \Illuminate\Http\Request::create('/fake', 'POST', $data)
        );
        $request->setUserResolver(fn() => $user);

        $rules = $request->rules();

        return Validator::make($data, $rules);
    }

    private function validPayload(): array
    {
        return [
            'first_name' => 'Omar',
            'last_name' => 'Allouni',
            'gender' => 'male',
            'dob' => now()->subYears(20)->toDateString(),
            'lat' => 33.5138,
            'lng' => 36.2765,
        ];
    }

    // ─── first_name ───────────────────────────────────────────────────────────

    public function test_first_name_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['first_name']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_first_name_cannot_exceed_50_characters(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), [
            'first_name' => str_repeat('a', 51),
        ]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    // ─── last_name ────────────────────────────────────────────────────────────

    public function test_last_name_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['last_name']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('last_name', $v->errors()->toArray());
    }

    // ─── gender ───────────────────────────────────────────────────────────────

    public function test_gender_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['gender']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('gender', $v->errors()->toArray());
    }

    public function test_gender_only_accepts_male_or_female(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['gender' => 'other']));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('gender', $v->errors()->toArray());
    }

    public function test_gender_accepts_female(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['gender' => 'female']));

        $this->assertFalse($v->fails());
    }

    // ─── dob / age ────────────────────────────────────────────────────────────

    public function test_dob_is_required(): void
    {
        $data = $this->validPayload();
        unset($data['dob']);

        $v = $this->validate($data);
        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('dob', $v->errors()->toArray());
    }

    public function test_dob_must_be_a_date(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), ['dob' => 'not-a-date']));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('dob', $v->errors()->toArray());
    }

    public function test_dob_rejects_age_under_16(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), [
            'dob' => now()->subYears(15)->toDateString(),
        ]));

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('dob', $v->errors()->toArray());
    }

    public function test_dob_accepts_age_over_16(): void
    {
        $v = $this->validate(array_merge($this->validPayload(), [
            'dob' => now()->subYears(17)->toDateString(),
        ]));

        $this->assertFalse($v->fails());
    }

    // ─── lat / lng ────────────────────────────────────────────────────────────

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

    // ─── phone (prohibited vs nullable branch) ────────────────────────────────

    public function test_phone_is_prohibited_for_user_who_already_has_one(): void
    {
        $v = $this->validate(
            array_merge($this->validPayload(), ['phone' => '+963911999999']),
            userHasPhone: true   // OTP user — phone already set
        );

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_phone_is_optional_for_google_user_without_phone(): void
    {
        // Google user with no phone — phone not sent → should pass
        $v = $this->validate(
            $this->validPayload(),
            userHasPhone: false
        );

        $this->assertFalse($v->fails());
    }

    // ─── full valid payload ───────────────────────────────────────────────────

    public function test_valid_payload_passes_all_rules(): void
    {
        $v = $this->validate($this->validPayload());

        $this->assertFalse($v->fails());
    }
}
