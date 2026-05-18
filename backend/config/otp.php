<?php

return [
    'driver' => env('OTP_DRIVER', 'null'),
    'ttl' => env('OTP_TTL', 300),
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
    'resend_cooldown' => env('OTP_RESEND_COOLDOWN', 60),

    /*
    |--------------------------------------------------------------------------
    | Fake OTP (testing/dev only)
    |--------------------------------------------------------------------------
    |
    | When OTP_FAKE=true, OTPService stores OTP_FAKE_CODE (default "000000")
    | instead of a random code, and skips dispatching the SendOtpJob.
    | NEVER enable this in production.
    |
    */
    'fake' => env('OTP_FAKE', false),
    'fake_code' => env('OTP_FAKE_CODE', '000000'),
];
