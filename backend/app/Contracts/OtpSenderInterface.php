<?php

namespace App\Contracts;

interface OtpSenderInterface
{
    public function send(string $phone, string $message): void;
}