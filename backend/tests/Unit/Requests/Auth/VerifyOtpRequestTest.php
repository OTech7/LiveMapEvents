<?php

namespace Tests\Unit\Requests\Auth;

use App\Http\Requests\Auth\VerifyOtpRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VerifyOtpRequestTest extends TestCase
{
    private function rules(): array
    {
        return (new VerifyOtpRequest())->rules();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $this->rules());
    }

    // ─── phone ────────────────────────────────────────────────────────────────

    public function test_phone_is_required(): void
    {
        $v = $this->validate(['otp' => '123456']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_phone_cannot_exceed_20_characters(): void
    {
        $v = $this->validate(['phone' => str_repeat('1', 21), 'otp' => '123456']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    // ─── otp ──────────────────────────────────────────────────────────────────

    public function test_otp_is_required(): void
    {
        $v = $this->validate(['phone' => '+963911000001']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('otp', $v->errors()->toArray());
    }

    public function test_otp_must_be_exactly_6_digits(): void
    {
        foreach (['12345', '1234567', 'abc123', ''] as $bad) {
            $v = $this->validate(['phone' => '+963911000001', 'otp' => $bad]);
            $this->assertTrue($v->fails(), "Expected '$bad' to fail the otp rule");
        }
    }

    public function test_otp_with_exactly_6_digits_passes(): void
    {
        $v = $this->validate(['phone' => '+963911000001', 'otp' => '123456']);

        $this->assertFalse($v->fails());
    }

    public function test_otp_must_contain_only_digits(): void
    {
        $v = $this->validate(['phone' => '+963911000001', 'otp' => 'abcdef']);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('otp', $v->errors()->toArray());
    }
}
