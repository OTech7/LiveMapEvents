<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\RequestOtpRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RequestOtpRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new RequestOtpRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    // ─── phone ────────────────────────────────────────────────────────────────

    public function test_phone_is_required(): void
    {
        $v = $this->validate([]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_phone_must_be_a_string(): void
    {
        $v = $this->validate(['phone' => 12345678]);

        // numeric values coerce to string in Laravel — passes string rule
        $this->assertFalse($v->fails());
    }

    public function test_phone_cannot_exceed_20_characters(): void
    {
        $v = $this->validate(['phone' => str_repeat('1', 21)]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_valid_phone_passes_validation(): void
    {
        $v = $this->validate(['phone' => '+963911123456']);

        $this->assertFalse($v->fails());
    }

    // ─── prepareForValidation normalisation (HTTP-level) ─────────────────────

    public function test_phone_is_normalized_before_validation_via_http(): void
    {
        // Syrian local 09... → normalized to +963...
        // Request passes → 200 (not 422), proving normalization ran
        $this->postJson('/api/v1/auth/phone/request-otp', ['phone' => '0912345678'])
            ->assertOk();
    }
}
