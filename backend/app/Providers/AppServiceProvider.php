<?php

namespace App\Providers;

use App\Contracts\OtpSenderInterface;
use App\Integrations\UltraMsgOtpSender;
use App\Support\CustomPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        Paginator::useBootstrapFive();
        $this->app->alias(CustomPaginator::class, LengthAwarePaginator::class);

        // We sit behind a TLS-terminating reverse proxy (Caddy). In production
        // force every generated URL (asset(), url(), route()) to use https://
        // — this guarantees Swagger UI, Sanctum redirects, password-reset
        // links, etc. never produce a mixed-content URL even if a single
        // X-Forwarded-Proto header is missing or fails to be trusted.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
