<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes — /api/admin/v1/*
|--------------------------------------------------------------------------
|
| These routes are mounted in bootstrap/app.php under the prefix
| `api/admin/v1`. They power the Next.js admin panel in /web.
|
| Auth model: same Sanctum tokens as the public mobile API. A user is
| considered an admin if they have the `admin` role (Spatie permissions).
| `/me` is reachable by any authenticated user so the panel can detect
| "logged in but not admin" and show a friendly error. Everything else
| is gated by `role:admin`.
|
*/

use App\Http\Controllers\Admin\V1\AuthController;
use App\Http\Controllers\Admin\V1\HealthController;
use App\Http\Controllers\Admin\V1\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Identity — does NOT require admin role, so the panel can show
    // "you're logged in but not an admin" rather than a 403 dead-end.
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Everything below requires the `admin` role.
    Route::middleware('role:admin')->group(function () {

        Route::get('/health', [HealthController::class, 'index']);

        // First resource. Will be replaced by the generic AdminResource
        // engine in Phase 3 of the plan — for now, hand-rolled.
        Route::get('/users', [UsersController::class, 'index']);
        Route::get('/users/{user}', [UsersController::class, 'show']);
        Route::put('/users/{user}', [UsersController::class, 'update']);
        Route::delete('/users/{user}', [UsersController::class, 'destroy']);
    });
});
