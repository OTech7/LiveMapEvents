<?php

namespace App\Contracts\OTP;

interface OtpSenderInterface
{
    public function send(string $phone, string $message): void;
}