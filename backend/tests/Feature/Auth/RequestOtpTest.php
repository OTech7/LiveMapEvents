<?php

namespace Tests\Feature\Auth;

use App\Services\OTPService;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RequestOtpTest extends TestCase
{
    public function test_request_otp_returns_success_when_sender_completes(): void
    {
        $this->mock(OTPService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once();
        });

        $response = $this->postJson('/api/v1/auth/phone/request-otp', [
            'phone' => '+963999999999',
        ]);

        $response
            ->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'success' => true,
                'message' => __('messages.otp_sent'),
            ]);
    }

    public function test_request_otp_returns_service_unavailable_when_provider_is_unreachable(): void
    {
        $this->mock(OTPService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new RuntimeException('Provider unreachable'));
        });

        $response = $this->postJson('/api/v1/auth/phone/request-otp', [
            'phone' => '+963999999999',
        ]);

        $response
            ->assertStatus(Response::HTTP_SERVICE_UNAVAILABLE)
            ->assertJson([
                'success' => false,
                'message' => __('messages.otp_provider_unreachable'),
                'data' => null,
            ]);
    }
}
