<?php

namespace Tests\Unit\DTOs;

use App\DTOs\OtpVerificationResult;
use App\Enums\OtpVerificationStatus;
use PHPUnit\Framework\TestCase;

class OtpVerificationResultTest extends TestCase
{
    public function test_is_verified_returns_true_when_status_is_verified(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::VERIFIED, 3);

        $this->assertTrue($result->isVerified());
    }

    public function test_is_verified_returns_false_when_status_is_invalid(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::INVALID, 2);

        $this->assertFalse($result->isVerified());
    }

    public function test_is_verified_returns_false_when_status_is_expired(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::EXPIRED);

        $this->assertFalse($result->isVerified());
    }

    public function test_is_verified_returns_false_when_status_is_max_attempts_reached(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::MAX_ATTEMPTS_REACHED, 0);

        $this->assertFalse($result->isVerified());
    }

    public function test_remaining_attempts_defaults_to_null(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::EXPIRED);

        $this->assertNull($result->remainingAttempts);
    }

    public function test_remaining_attempts_can_be_set(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::INVALID, 2);

        $this->assertSame(2, $result->remainingAttempts);
    }

    public function test_status_is_accessible_as_public_property(): void
    {
        $result = new OtpVerificationResult(OtpVerificationStatus::VERIFIED, 3);

        $this->assertSame(OtpVerificationStatus::VERIFIED, $result->status);
    }
}
