<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\PinController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('lang')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/phone/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/google', [AuthController::class, 'googleLogin']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/complete-profile', [ProfileController::class, 'completeProfile']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {

        // Global interest catalog (read-only) — used by the mobile picker UI
        Route::get('/interests', [InterestController::class, 'index']);

        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::put('/discovery-settings', [ProfileController::class, 'updateDiscoverySettings']);

            // Authenticated user's interests (route-model-bound by slug)
            Route::get('/interests', [ProfileController::class, 'myInterests']);
            Route::put('/interests', [ProfileController::class, 'updateInterests']);
            Route::post('/interests/{interest}', [ProfileController::class, 'addInterest']);
            Route::delete('/interests/{interest}', [ProfileController::class, 'removeInterest']);
        });

        Route::get('/pins/nearby', [PinController::class, 'nearby']);
    });

});
