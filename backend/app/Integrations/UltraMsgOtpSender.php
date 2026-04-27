<?php

namespace App\Integrations;

use App\Contracts\OtpSenderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UltraMsgOtpSender implements OtpSenderInterface
{
    public function send(string $phone, string $message): void
    {
        $url = config('services.ultramsg.url');
        $token = config('services.ultramsg.token');

        // UltraMsg's WhatsApp API expects 'to' as digits with country code,
        // WITHOUT the leading '+'. Strip it here at the gateway boundary so
        // the rest of the app can keep using +E.164 internally.
        $to = ltrim($phone, '+');

        Log::info('UltraMsg send attempt', [
            'url' => $url,
            'phone' => $phone,
            'to' => $to,
            'message' => $message,
            'token_exists' => !empty($token),
        ]);

        $response = Http::asForm()
            ->timeout(20)
            ->post($url, [
                'token' => $token,
                'to' => $to,
                'body' => $message,
            ]);

        Log::info('UltraMsg response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'failed' => $response->failed(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('UltraMsg request failed: ' . $response->body());
        }
    }
}