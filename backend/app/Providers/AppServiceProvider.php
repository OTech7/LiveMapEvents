<?php

namespace App\Providers;

use App\Contracts\OtpSenderInterface;
use App\Integrations\UltraMsgOtpSender;
use App\Models\Promotion;
use App\Modules\Admin\AdminResources;
use App\Policies\PromotionPolicy;
use App\Support\CustomPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        // ─── Policies ─────────────────────────────────────────────────────────
        Gate::policy(Promotion::class, PromotionPolicy::class);

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

        // Admin panel: resolve {admin_resource} URL slug → AdminResource
        // instance for routes/admin.php. Lives here (not in the route file)
        // so it survives `php artisan route:cache` — the entrypoint runs
        // route:cache on every container boot, and bindings registered
        // inline in cached route files don't always re-fire.
        Route::bind('admin_resource', function (string $value) {
            $resource = AdminResources::find($value);
            abort_if($resource === null, 404, "Unknown admin resource: {$value}");
            return $resource;
        });
    }
}
