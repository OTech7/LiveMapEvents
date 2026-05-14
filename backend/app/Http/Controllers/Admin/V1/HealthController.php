<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            message: 'messages.success',
            data: [
                'service' => 'admin',
                'time' => now()->toIso8601String(),
            ]
        );
    }
}
