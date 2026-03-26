<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Log;

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

});