<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class VerifyOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_verify_otp_and_login()
    {
        $phone = '123456789';
        $otp = '123456';

        Redis::setex("otp:$phone", 300, json_encode([
            'code' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $response = $this->verifyOtpFor($phone,$otp);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['token', 'user', 'profile_complete']
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => $phone
        ]);
    }

    public function test_invalid_otp_returns_error()
    {
        $phone = '123456789';

        Redis::setex("otp:$phone", 300, json_encode([
            'code' => Hash::make('111111'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));
        
        $response = $this->verifyOtpFor($phone,'000000');

        $response->assertStatus(422);
    }

    public function test_expired_otp_returns_error()
    {
        $phone = '123456789';

        Redis::setex("otp:$phone", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->subMinute()->timestamp,
        ]));

        $response = $this->verifyOtpFor($phone,'123456');

        $response->assertStatus(422);
    }

    protected function verifyOtpFor($phone , $otp)
    {
        return $this->postJson('/api/v1/auth/phone/verify-otp', ['phone' => $phone , 'otp' => $otp]);
    }
}
