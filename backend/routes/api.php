<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PinController;
use App\Http\Controllers\Api\V1\ProfileController;

Route::prefix('v1')->middleware('lang')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp',[AuthController::class,'requestOtp']);
        Route::post('/phone/verify-otp',[AuthController::class,'verifyOtp']);
        Route::post('/google',[AuthController::class,'googleLogin']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me',[AuthController::class,'me']);
            Route::post('/complete-profile', [ProfileController::class, 'completeProfile']);
            Route::post('/logout',[AuthController::class,'logout']);
        });
    });
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::put('/interests', [ProfileController::class, 'updateInterests']);
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::put('/discovery-settings', [ProfileController::class, 'updateDiscoverySettings']);
        });
        Route::get('/pins/nearby', [PinController::class, 'nearby']);
    });

});