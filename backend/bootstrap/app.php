<?php

use App\Http\Middleware\SetLocale;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
             'lang' => SetLocale::class,
             'auth' => \App\Http\Middleware\Authenticate::class,
        ]);

        // We sit behind Caddy (livemap-caddy on the docker bridge). Trust the
        // X-Forwarded-* headers it sends so url() / asset() / Swagger generate
        // https:// URLs and the request scheme is correctly detected as HTTPS.
        // 'at: *' is safe here because nothing else can reach this container —
        // it only listens on the internal docker network (no host port).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // API requests should return 401 JSON, not redirect to a login page
        $middleware->redirectGuestsTo(fn ($request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'messages.validation_error',
                    $e->errors(),
                    422
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            return ApiResponse::error(
                'messages.unauthorized',
                null,
                401
            );
        });
    })
    ->create();
