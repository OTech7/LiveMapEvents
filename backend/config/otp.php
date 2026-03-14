<?php

return [
    'driver' => env('OTP_DRIVER', 'null'),
    'ttl' => env('OTP_TTL', 300),
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
    'resend_cooldown' => env('OTP_RESEND_COOLDOWN', 60),
];