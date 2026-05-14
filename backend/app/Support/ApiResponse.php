<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * Shape is always:
     *   { success: true, message: string, data: mixed, errors: null }
     */
    public static function success(
        string $message = 'messages.success',
        mixed  $data = null,
        int    $status = 200
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ? __($message) : __('messages.success'),
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    /**
     * Return an error JSON response.
     *
     * Shape is always:
     *   { success: false, message: string, data: null, errors: mixed }
     */
    public static function error(
        string $message,
        mixed  $errors = null,
        int    $status = 400
    ): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __($message),
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
