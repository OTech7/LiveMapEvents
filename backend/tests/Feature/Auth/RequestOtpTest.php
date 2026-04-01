<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendOtpJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RequestOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp()
    {
        Queue::fake();

        $response = $this->requestOtpFor('123456789');

        $response->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(SendOtpJob::class);
    }

    public function test_user_cannot_request_otp_during_cooldown()
    {
        Redis::setex('otp_cooldown:123456789', 60, 1);

        $response = $this->requestOtpFor('123456789');

        $response->assertStatus(422);
    }

    protected function requestOtpFor($phone)
    {
        return $this->postJson('/api/v1/auth/phone/request-otp', ['phone' => $phone]);
    }
    
}
