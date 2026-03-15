<?php

namespace App\Enums\OTP;

enum OtpVerificationStatus: string
{
    case VERIFIED = 'verified';
    case INVALID = 'invalid';
    case EXPIRED = 'expired';
    case MAX_ATTEMPTS_REACHED = 'max_attempts_reached';
}