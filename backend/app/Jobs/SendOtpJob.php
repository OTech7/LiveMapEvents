<?php

namespace App\Jobs;

use App\Contracts\OtpSenderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public string $phone,
        public string $code
    ) {}

    public function handle(OtpSenderInterface $sender): void
    {
        Log::info('SendOtpJob started', [
            'phone' => $this->phone,
        ]);

        $message = trans('messages.your_otp_code', [
            'code' => $this->code,
        ]);

        $sender->send($this->phone, $message);

        Log::info('SendOtpJob finished', [
            'phone' => $this->phone,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendOtpJob failed', [
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
        ]);
    }
}