<?php

namespace App\Services;

use App\DTOs\OtpVerificationResult;
use App\Enums\OtpVerificationStatus;
use App\Jobs\SendOtpJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class OTPService
{
    private int $ttl;
    private int $maxAttempts;
    private int $resendCooldown;

    public function __construct()
    {
        $this->ttl = (int) config('otp.ttl', 300); // 5 min
        $this->maxAttempts = (int) config('otp.max_attempts', 3);
        $this->resendCooldown = (int) config('otp.resend_cooldown', 60);
    }

    public function send(string $phone): void
    {
        $rateKey = $this->rateLimitKey($phone);
        $cooldownKey = $this->cooldownKey($phone);

        $requests = Redis::get($rateKey);

        if ($requests && $requests >= $this->maxAttempts)
        {
            throw ValidationException::withMessages([
                'otp' => [__('messages.too_many_otp_requests')]
            ]);
        }

        if (Redis::exists($cooldownKey)) {
            throw ValidationException::withMessages([
                'otp' => [__('messages.otp_cooldown')]
            ]);
        }

        $code = (string) random_int(100000, 999999);

        $payload = [
            'code' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addSeconds($this->ttl)->timestamp,
        ];

        Redis::setex(
            $this->otpKey($phone),
            $this->ttl,
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        Redis::setex($cooldownKey, $this->resendCooldown, 1);

        if (! $requests) {
            Redis::setex($rateKey, 3600, 1); 
        } else {
            Redis::incr($rateKey);
        }

        dispatch(new SendOtpJob($phone, $code));
    }

    public function verify(string $phone, string $otp): OtpVerificationResult
    {
        $otpKey = $this->otpKey($phone);
        $lockKey = $this->lockKey($phone);

        if (Redis::exists($lockKey)) {
            return new OtpVerificationResult(
                OtpVerificationStatus::MAX_ATTEMPTS_REACHED,
                0
            );
        }

        $rawData = Redis::get($otpKey);

        if (! $rawData) {
            return new OtpVerificationResult(
                OtpVerificationStatus::EXPIRED
            );
        }

        $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);

        if (($data['expires_at'] ?? 0) < now()->timestamp) {
            Redis::del($otpKey);

            return new OtpVerificationResult(
                OtpVerificationStatus::EXPIRED
            );
        }

        if (! Hash::check($otp, $data['code'])) {
            $data['attempts']++;

            if ($data['attempts'] >= $this->maxAttempts) {
                Redis::setex($lockKey, 900, 1); // 15 min lock
            }

            $remainingTtl = Redis::ttl($otpKey);
            $remainingTtl = $remainingTtl > 0 ? $remainingTtl : $this->ttl;

            Redis::setex(
                $otpKey,
                $remainingTtl,
                json_encode($data, JSON_THROW_ON_ERROR)
            );

            return new OtpVerificationResult(
                OtpVerificationStatus::INVALID,
                max(0, $this->maxAttempts - $data['attempts'])
            );
        }

        Redis::del($otpKey);

        return new OtpVerificationResult(OtpVerificationStatus::VERIFIED,$this->maxAttempts);
    }

    private function otpKey(string $phone): string
    {
        return "otp:{$phone}";
    }

    private function cooldownKey(string $phone): string
    {
        return "otp_cooldown:{$phone}";
    }

    private function lockKey(string $phone): string
    {
        return "otp_lock:{$phone}";
    }

    private function rateLimitKey(string $phone): string
    {
        return "otp_rate:{$phone}";
    }
}