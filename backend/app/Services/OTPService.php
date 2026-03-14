<?php

namespace App\Services;

use App\DTOs\OTP\OtpVerificationResult;
use App\Enums\OTP\OtpVerificationStatus;
use App\Jobs\SendOtpJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class OTPService
{
    private int $ttl;
    private int $maxAttempts;
    private int $resendCooldown;

    public function __construct()
    {
        $this->ttl = (int) config('otp.ttl', 300);
        $this->maxAttempts = (int) config('otp.max_attempts', 3);
        $this->resendCooldown = (int) config('otp.resend_cooldown', 60);
    }

    public function send(string $phone): void
    {
        $cooldownKey = $this->cooldownKey($phone);

        if (Redis::exists($cooldownKey)) {
            return;
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

        dispatch(new SendOtpJob($phone, $code));
    }

    public function verify(string $phone, string $otp): OtpVerificationResult
    {
        $rawData = Redis::get($this->otpKey($phone));

        if (! $rawData) {
            return new OtpVerificationResult(
                OtpVerificationStatus::EXPIRED
            );
        }

        $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);

        if (($data['attempts'] ?? 0) >= $this->maxAttempts) {
            return new OtpVerificationResult(
                OtpVerificationStatus::MAX_ATTEMPTS_REACHED,
                0
            );
        }

        if (($data['expires_at'] ?? 0) < now()->timestamp) {
            Redis::del($this->otpKey($phone));

            return new OtpVerificationResult(
                OtpVerificationStatus::EXPIRED
            );
        }

        if (! Hash::check($otp, $data['code'])) {
            $data['attempts']++;

            $remainingTtl = Redis::ttl($this->otpKey($phone));
            $remainingTtl = $remainingTtl > 0 ? $remainingTtl : $this->ttl;

            Redis::setex(
                $this->otpKey($phone),
                $remainingTtl,
                json_encode($data, JSON_THROW_ON_ERROR)
            );

            return new OtpVerificationResult(
                OtpVerificationStatus::INVALID,
                max(0, $this->maxAttempts - $data['attempts'])
            );
        }

        Redis::del($this->otpKey($phone));

        return new OtpVerificationResult(
            OtpVerificationStatus::VERIFIED,
            $this->maxAttempts
        );
    }

    private function otpKey(string $phone): string
    {
        return "otp:{$phone}";
    }

    private function cooldownKey(string $phone): string
    {
        return "otp_cooldown:{$phone}";
    }
}