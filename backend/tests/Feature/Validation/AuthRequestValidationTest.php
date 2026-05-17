<?php

namespace Tests\Feature\Validation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * HTTP-level validation tests for Auth FormRequests.
 * Exercises prepareForValidation() hooks and rule constraints
 * by hitting the real routes with invalid payloads.
 */
class AuthRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    // ─── RequestOtpRequest ────────────────────────────────────────────────────

    public function test_request_otp_requires_phone(): void
    {
        $this->postJson('/api/v1/auth/phone/request-otp', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_request_otp_normalizes_phone_before_validation(): void
    {
        // Syrian local format → normalised → valid (no 422 from phone rule)
        // The OTP service will handle it, we just need the 200 to confirm
        // the request passed validation successfully.
        $response = $this->postJson('/api/v1/auth/phone/request-otp', [
            'phone' => '0912345678', // Syrian local → +9639...
        ]);

        // 200 means validation passed (OTP was sent / fake mode)
        $response->assertOk();
    }

    public function test_request_otp_rejects_phone_exceeding_max_length(): void
    {
        $this->postJson('/api/v1/auth/phone/request-otp', [
            'phone' => str_repeat('1', 25),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    // ─── VerifyOtpRequest ─────────────────────────────────────────────────────

    public function test_verify_otp_requires_phone(): void
    {
        $this->postJson('/api/v1/auth/phone/verify-otp', ['otp' => '123456'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_verify_otp_requires_otp(): void
    {
        $this->postJson('/api/v1/auth/phone/verify-otp', ['phone' => '+963911000001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_rejects_non_6_digit_otp(): void
    {
        $this->postJson('/api/v1/auth/phone/verify-otp', [
            'phone' => '+963911000001',
            'otp' => '123',   // only 3 digits
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_rejects_alphabetic_otp(): void
    {
        $this->postJson('/api/v1/auth/phone/verify-otp', [
            'phone' => '+963911000001',
            'otp' => 'abcdef',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_otp_strips_whitespace_from_otp(): void
    {
        $phone = '+963911000002';
        $otp = '123456';

        Redis::setex("otp:{$phone}", 300, json_encode([
            'code' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        // OTP with spaces → should be stripped by prepareForValidation()
        $this->postJson('/api/v1/auth/phone/verify-otp', [
            'phone' => $phone,
            'otp' => '12 34 56',
        ])->assertOk();
    }

    // ─── GoogleLoginRequest ───────────────────────────────────────────────────

    public function test_google_login_requires_id_token(): void
    {
        $this->postJson('/api/v1/auth/google', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_token']);
    }

    public function test_google_login_id_token_must_be_string(): void
    {
        $this->postJson('/api/v1/auth/google', ['id_token' => 12345])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_token']);
    }

    // ─── CompleteProfileRequest ───────────────────────────────────────────────

    public function test_complete_profile_requires_first_name(): void
    {
        $user = User::create(['phone' => '+963911000010', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'last_name' => 'Doe', 'gender' => 'male',
                'dob' => '1990-01-01', 'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_complete_profile_requires_last_name(): void
    {
        $user = User::create(['phone' => '+963911000011', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'gender' => 'male',
                'dob' => '1990-01-01', 'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_complete_profile_requires_gender(): void
    {
        $user = User::create(['phone' => '+963911000012', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'dob' => '1990-01-01', 'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_complete_profile_rejects_invalid_gender_value(): void
    {
        $user = User::create(['phone' => '+963911000013', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'other', 'dob' => '1990-01-01',
                'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['gender']);
    }

    public function test_complete_profile_enforces_minimum_age_of_16(): void
    {
        $user = User::create(['phone' => '+963911000014', 'profile_complete' => false]);

        $underageDob = now()->subYears(15)->toDateString();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'male', 'dob' => $underageDob,
                'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dob']);
    }

    public function test_complete_profile_accepts_dob_for_user_exactly_16(): void
    {
        $user = User::create(['phone' => '+963911000015', 'profile_complete' => false]);

        $exactly16 = now()->subYears(16)->subDay()->toDateString();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'male', 'dob' => $exactly16,
                'lat' => 33.5, 'lng' => 36.3,
            ])
            ->assertSuccessful();
    }

    public function test_complete_profile_rejects_lat_out_of_bounds(): void
    {
        $user = User::create(['phone' => '+963911000016', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'male', 'dob' => '1990-01-01',
                'lat' => 95.0, 'lng' => 36.3,   // > 90
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat']);
    }

    public function test_complete_profile_rejects_lng_out_of_bounds(): void
    {
        $user = User::create(['phone' => '+963911000017', 'profile_complete' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'male', 'dob' => '1990-01-01',
                'lat' => 33.5, 'lng' => 185.0,   // > 180
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lng']);
    }

    public function test_complete_profile_prohibits_phone_for_otp_user_who_already_has_one(): void
    {
        $user = User::create([
            'phone' => '+963911000018',
            'profile_complete' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'John', 'last_name' => 'Doe',
                'gender' => 'male', 'dob' => '1990-01-01',
                'lat' => 33.5, 'lng' => 36.3,
                'phone' => '+963911999999',  // should be prohibited
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_complete_profile_rejects_duplicate_phone_for_google_user(): void
    {
        User::create(['phone' => '+963911000019']); // already taken
        $googleUser = User::create(['google_id' => 'gid-1', 'profile_complete' => false]);

        $this->actingAs($googleUser, 'sanctum')
            ->postJson('/api/v1/auth/complete-profile', [
                'first_name' => 'Jane', 'last_name' => 'Doe',
                'gender' => 'female', 'dob' => '1990-01-01',
                'lat' => 33.5, 'lng' => 36.3,
                'phone' => '+963911000019',  // duplicate
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }
}
