<?php

namespace Tests\Unit\Services;

use App\Enums\OtpVerificationStatus;
use App\Jobs\SendOtpJob;
use App\Services\OTPService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Tests\UnitTestCase;

class OTPServiceTest extends UnitTestCase
{
    private OTPService $service;

    protected function setUp(): void
    {
        $this->requireRedis(); // skip cleanly when Docker is not running
        parent::setUp();
        $this->service = app(OTPService::class);

        // Clean up any Redis keys that might bleed between tests
        Redis::del('otp:+963911999999');
        Redis::del('otp_cooldown:+963911999999');
        Redis::del('otp_rate:+963911999999');
        Redis::del('otp_lock:+963911999999');
    }

    protected function tearDown(): void
    {
        Redis::del('otp:+963911999999');
        Redis::del('otp_cooldown:+963911999999');
        Redis::del('otp_rate:+963911999999');
        Redis::del('otp_lock:+963911999999');
        parent::tearDown();
    }

    private string $phone = '+963911999999';

    // ─── send() ───────────────────────────────────────────────────────────────

    public function test_send_stores_otp_payload_in_redis(): void
    {
        config(['otp.fake' => true, 'otp.fake_code' => '123456']);

        $this->service->send($this->phone);

        $raw = Redis::get("otp:{$this->phone}");
        $this->assertNotNull($raw);

        $data = json_decode($raw, true);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('attempts', $data);
        $this->assertArrayHasKey('expires_at', $data);
        $this->assertSame(0, $data['attempts']);
    }

    public function test_send_uses_fake_code_when_fake_mode_enabled(): void
    {
        config(['otp.fake' => true, 'otp.fake_code' => '000000']);

        $this->service->send($this->phone);

        $data = json_decode(Redis::get("otp:{$this->phone}"), true);
        $this->assertTrue(Hash::check('000000', $data['code']));
    }

    public function test_send_dispatches_job_when_fake_mode_is_disabled(): void
    {
        Queue::fake();
        config(['otp.fake' => false]);

        $this->service->send($this->phone);

        Queue::assertPushed(SendOtpJob::class);
    }

    public function test_send_does_not_dispatch_job_when_fake_mode_is_enabled(): void
    {
        Queue::fake();
        config(['otp.fake' => true, 'otp.fake_code' => '123456']);

        $this->service->send($this->phone);

        Queue::assertNotPushed(SendOtpJob::class);
    }

    public function test_send_throws_validation_exception_during_cooldown(): void
    {
        Redis::setex("otp_cooldown:{$this->phone}", 60, 1);

        $this->expectException(ValidationException::class);

        $this->service->send($this->phone);
    }

    public function test_send_throws_validation_exception_at_max_rate_limit(): void
    {
        config(['otp.max_attempts' => 3]);
        Redis::setex("otp_rate:{$this->phone}", 3600, 3);

        $this->expectException(ValidationException::class);

        $this->service->send($this->phone);
    }

    public function test_send_sets_cooldown_key_after_sending(): void
    {
        config(['otp.fake' => true, 'otp.fake_code' => '000000']);

        $this->service->send($this->phone);

        $this->assertTrue((bool)Redis::exists("otp_cooldown:{$this->phone}"));
    }

    // ─── verify() ─────────────────────────────────────────────────────────────

    public function test_verify_returns_verified_for_correct_otp(): void
    {
        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $result = $this->service->verify($this->phone, '123456');

        $this->assertSame(OtpVerificationStatus::VERIFIED, $result->status);
        $this->assertTrue($result->isVerified());
    }

    public function test_verify_deletes_otp_key_after_successful_verification(): void
    {
        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $this->service->verify($this->phone, '123456');

        $this->assertFalse((bool)Redis::exists("otp:{$this->phone}"));
    }

    public function test_verify_returns_invalid_for_wrong_otp(): void
    {
        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $result = $this->service->verify($this->phone, '000000');

        $this->assertSame(OtpVerificationStatus::INVALID, $result->status);
        $this->assertFalse($result->isVerified());
    }

    public function test_verify_increments_attempts_on_wrong_otp(): void
    {
        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $this->service->verify($this->phone, '000000');

        $data = json_decode(Redis::get("otp:{$this->phone}"), true);
        $this->assertSame(1, $data['attempts']);
    }

    public function test_verify_returns_expired_when_no_otp_in_redis(): void
    {
        $result = $this->service->verify($this->phone, '123456');

        $this->assertSame(OtpVerificationStatus::EXPIRED, $result->status);
    }

    public function test_verify_returns_expired_when_otp_timestamp_has_passed(): void
    {
        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->subMinute()->timestamp,
        ]));

        $result = $this->service->verify($this->phone, '123456');

        $this->assertSame(OtpVerificationStatus::EXPIRED, $result->status);
    }

    public function test_verify_returns_max_attempts_when_lock_key_exists(): void
    {
        Redis::setex("otp_lock:{$this->phone}", 900, 1);

        $result = $this->service->verify($this->phone, '123456');

        $this->assertSame(OtpVerificationStatus::MAX_ATTEMPTS_REACHED, $result->status);
    }

    public function test_verify_sets_lock_after_max_wrong_attempts(): void
    {
        config(['otp.max_attempts' => 3]);

        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 2, // one more wrong attempt → lock
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $this->service->verify($this->phone, '000000');

        $this->assertTrue((bool)Redis::exists("otp_lock:{$this->phone}"));
    }

    public function test_verify_remaining_attempts_decrements_correctly(): void
    {
        config(['otp.max_attempts' => 3]);

        Redis::setex("otp:{$this->phone}", 300, json_encode([
            'code' => Hash::make('123456'),
            'attempts' => 1, // 1 already used → 1 remaining after this wrong attempt
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $result = $this->service->verify($this->phone, '000000');

        $this->assertSame(OtpVerificationStatus::INVALID, $result->status);
        $this->assertSame(1, $result->remainingAttempts);
    }
}
