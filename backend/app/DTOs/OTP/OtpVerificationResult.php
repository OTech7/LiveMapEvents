<?php

namespace App\DTOs\OTP;

use App\Enums\OTP\OtpVerificationStatus;

class OtpVerificationResult
{
    public function __construct(
        public OtpVerificationStatus $status,
        public ?int $remainingAttempts = null,
    ) {}

    public function isVerified(): bool
    {
        return $this->status === OtpVerificationStatus::VERIFIED;
    }
}