<?php

namespace App\Exceptions;

use Exception;
use App\Support\ApiResponse;

class ApiException extends Exception
{
    protected string $messageKey;
    protected int $statusCode;

    public function __construct(
        string $messageKey = 'messages.server_error',
        int $statusCode = 500
    ) {
        parent::__construct($messageKey);

        $this->messageKey = $messageKey;
        $this->statusCode = $statusCode;
    }

    public function render($request)
    {
        if ($request->is('api/*')) {
            return ApiResponse::error(
                $this->messageKey,
                null,
                $this->statusCode
            );
        }
    }
}