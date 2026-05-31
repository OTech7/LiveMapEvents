<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Business\EventController as BusinessEventController;
use App\Http\Controllers\Api\V1\Business\PromotionController as BusinessPromotionController;
use App\Http\Controllers\Api\V1\Business\ScannerController;
use App\Http\Controllers\Api\V1\Business\VenueController as BusinessVenueController;
use App\Http\Controllers\Api\V1\InterestController;
use App\Http\Controllers\Api\V1\PinController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PromotionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('lang')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/phone/request-otp', [AuthController::class, 'requestOtp'])
            ->middleware('throttle:5,1'); // 5 req/min per IP (in addition to per-phone limit inside OTPService)
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

        // ── Business owner: manage their venues ──────────────────────────────
        Route::prefix('business/venues')->group(function () {
            Route::get('/', [BusinessVenueController::class, 'index']);
            Route::post('/', [BusinessVenueController::class, 'store']);
            Route::get('{venue}', [BusinessVenueController::class, 'show']);
            Route::put('{venue}', [BusinessVenueController::class, 'update']);
            Route::delete('{venue}', [BusinessVenueController::class, 'destroy']);
        });

        // ── Business owner: manage their promotions ───────────────────────────
        Route::prefix('business')->group(function () {
            Route::get('promotions', [BusinessPromotionController::class, 'index']);
            Route::post('promotions', [BusinessPromotionController::class, 'store']);
            Route::get('promotions/{promotion}', [BusinessPromotionController::class, 'show']);
            Route::put('promotions/{promotion}', [BusinessPromotionController::class, 'update']);
            Route::delete('promotions/{promotion}', [BusinessPromotionController::class, 'destroy']);
            Route::get('promotions/{promotion}/claims', [BusinessPromotionController::class, 'claims']);
            Route::post('scanner/redeem', [ScannerController::class, 'redeem']);

            // ── Business owner: manage their events ───────────────────────────────
            Route::prefix('events')->group(function () {
                Route::get('/', [BusinessEventController::class, 'index']);
                Route::post('/', [BusinessEventController::class, 'store']);
                Route::get('{event}', [BusinessEventController::class, 'show']);
                Route::put('{event}', [BusinessEventController::class, 'update']);
                Route::delete('{event}', [BusinessEventController::class, 'destroy']);
                Route::post('{event}/cancel', [BusinessEventController::class, 'cancel']);
            });
        });

        // ── User: discover and claim promotions ───────────────────────────────
        Route::get('promotions/nearby', [PromotionController::class, 'nearby']);
        Route::get('promotions/{promotion}', [PromotionController::class, 'show']);
        Route::post('promotions/{promotion}/claim', [PromotionController::class, 'claim']);
        Route::get('me/claims', [PromotionController::class, 'myClaims']);
    });

});
