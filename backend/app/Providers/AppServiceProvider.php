<?php

namespace App\Providers;

use App\Contracts\OTP\OtpSenderInterface;
use App\Services\OTP\Senders\UltraMsgOtpSender;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpSenderInterface::class, function () {
            return match (config('otp.driver')) {
                'ultramsg' => app(UltraMsgOtpSender::class),
                default => throw new InvalidArgumentException('Unsupported OTP driver.'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
