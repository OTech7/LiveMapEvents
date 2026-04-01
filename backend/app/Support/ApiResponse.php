<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(string $message = 'messages.success',mixed $data = null,int $status = 200): JsonResponse {

        return response()->json([
            'success' => true,
            'message' => $message ? __($message) : null,
            'data' => $data,
            'errors' => null
        ], $status);
    }

    public static function error(string $message,mixed $errors = null,int $status = 400): JsonResponse {

        return response()->json([
            'success' => false,
            'message' => __($message),
            'data' => null,
            'errors' => $errors
        ], $status);
    }
}