<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\PaymentController;

// Include v1 routes
include __DIR__ . '/api/v1.php';

// Canonical webhook endpoint
// - POST /api/v1/webhooks/payway
Route::post('/v1/webhooks/payway', [PaymentController::class, 'webhookCanonical']);
