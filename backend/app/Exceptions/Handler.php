<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Support\ApiResponse;
USE App\Exceptions\InvalidOtpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {

            if ($e instanceof ValidationException) {
                return ApiResponse::error(
                    'messages.validation_error',
                    $e->errors(),
                    422
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error(
                    'messages.unauthorized',
                    null,
                    401
                );
            }

            if ($e instanceof HttpException) {
                return ApiResponse::error(
                    $e->getMessage() ?: 'messages.error',
                    null,
                    $e->getStatusCode()
                );
            }

            if ($e instanceof InvalidOtpException) {
                return ApiResponse::error(
                    $e->getMessage(),
                    null,
                    401
                );
            }

            if (config('app.debug')) {
                return ApiResponse::error(
                    $e->getMessage(),
                    [
                        'trace' => $e->getTrace()
                    ],
                    500
                );
            }

            return ApiResponse::error(
                'messages.server_error',
                null,
                500
            );
        }

        return parent::render($request, $e);
    }
}
