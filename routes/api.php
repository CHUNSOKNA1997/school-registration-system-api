<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\PaymentController;

// Include v1 routes
include __DIR__ . '/api/v1.php';

// Payway Payment Routes
Route::group(['prefix' => 'v1/payway'], function () {
    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/khqr/generate', [PaymentController::class, 'generateKHQR']);
        Route::post('/payment/status', [PaymentController::class, 'checkStatus']);
    });

    // Webhook route (no authentication - PayWay callback)
    Route::post('/webhook', [PaymentController::class, 'webhook']);
});
