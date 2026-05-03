<?php

namespace App\Integrations;

use App\Contracts\OtpSenderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UltraMsgOtpSender implements OtpSenderInterface
{
    public function send(string $phone, string $message): void
    {
        $url = $this->resolveUrl();
        $token = trim((string) config('services.ultramsg.token', ''));

        if ($token === '') {
            throw new RuntimeException('ULTRAMSG_TOKEN is missing.');
        }

        Log::info('UltraMsg send attempt', [
            'url' => $url,
            'phone' => $phone,
            'message' => $message,
            'token_exists' => !empty($token),
        ]);

        try {
            $response = Http::asForm()
                ->connectTimeout(10)
                ->timeout(20)
                ->retry(2, 500, null, false)
                ->post($url, [
                    'token' => $token,
                    'to' => $phone,
                    'body' => $message,
                ]);
        } catch (ConnectionException $exception) {
            Log::error('UltraMsg connection error', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'OTP provider is unreachable. Check DNS or internet connectivity for api.ultramsg.com.',
                0,
                $exception
            );
        }

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

    private function resolveUrl(): string
    {
        $configuredUrl = trim((string) config('services.ultramsg.url', ''));

        if ($configuredUrl !== '') {
            return $configuredUrl;
        }

        $instanceId = trim((string) config('services.ultramsg.instanceId', ''));

        if ($instanceId === '') {
            throw new RuntimeException('UltraMsg URL is missing. Set ULTRAMSG_URL or ULTRAMSG_INSTANCE_ID.');
        }

        return sprintf('https://api.ultramsg.com/%s/messages/chat', $instanceId);
    }
}
