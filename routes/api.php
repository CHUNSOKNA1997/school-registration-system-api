<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\PaymentController;

Route::group(['prefix' => 'user'], function () {
    include __DIR__ . '/api/v1.php';
});

// Payway Payment Routes
Route::group(['prefix' => 'payway'], function () {
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/khqr/generate', [PaymentController::class, 'generateKHQR']);
        Route::post('/payment/status', [PaymentController::class, 'checkStatus']);
    });

    // Webhook route (no authentication - PayWay callback)
    Route::post('/webhook', [PaymentController::class, 'webhook']);

    // TEST ROUTE - Remove this in production!
    Route::post('/test/khqr', [PaymentController::class, 'generateKHQR'])->name('payway.test.khqr');
});
