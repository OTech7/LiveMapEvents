<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;

class HealthController extends Controller
{
    public function index()
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
