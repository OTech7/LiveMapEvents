<?php

/*
|--------------------------------------------------------------------------
| Admin API Routes — /api/admin/v1/*
|--------------------------------------------------------------------------
|
| Mounted in bootstrap/app.php under the prefix `api/admin/v1`. Powers the
| Next.js admin panel in /web.
|
| Auth model: same Sanctum tokens as the public mobile API.
|   - /me, /logout — any authenticated user (so the panel can detect "logged
|     in but no panel role" and render a friendly error).
|   - /health      — admin-only sanity probe.
|   - /<resource>/* — resolved via Route::bind('admin_resource') below; the
|     ResourceController applies the per-action permission gate
|     (users.view / users.update / …) using the resource's permission() base.
|
| The routes are static (no closures) so `php artisan route:cache` works
| in production. The dynamic part is the `{admin_resource}` slug.
|
*/

use App\Http\Controllers\Admin\V1\AuthController;
use App\Http\Controllers\Admin\V1\HealthController;
use App\Http\Controllers\Admin\V1\ResourceController;
use App\Http\Controllers\Admin\V1\UserInterestsController;
use Illuminate\Support\Facades\Route;

// Note: {admin_resource} → AdminResource instance binding is registered in
// AppServiceProvider::boot() so it survives `php artisan route:cache`.

Route::middleware('auth:sanctum')->group(function () {

    // Identity — does NOT require any role/permission.
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Health probe — admin-only.
    Route::get('/health', [HealthController::class, 'index'])
        ->middleware('role:admin');

    // User-specific nested routes — must be declared BEFORE the generic
    // {admin_resource} catch-alls so Laravel matches these first.
    Route::get('/users/{user}/interests', [UserInterestsController::class, 'index']);
    Route::put('/users/{user}/interests', [UserInterestsController::class, 'sync']);

    // Generic resource CRUD. Each method authorises against the resource's
    // permission base internally — see ResourceController.
    Route::get('/{admin_resource}/schema', [ResourceController::class, 'schema']);
    Route::get('/{admin_resource}', [ResourceController::class, 'index']);
    Route::post('/{admin_resource}', [ResourceController::class, 'store']);
    Route::get('/{admin_resource}/{key}', [ResourceController::class, 'show']);
    Route::put('/{admin_resource}/{key}', [ResourceController::class, 'update']);
    Route::delete('/{admin_resource}/{key}', [ResourceController::class, 'destroy']);
});
