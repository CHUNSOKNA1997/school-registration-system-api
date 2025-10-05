<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestKhqrController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Test KHQR Routes (for development/testing only)
Route::group(['prefix' => 'test/khqr'], function () {
    Route::get('/', [TestKhqrController::class, 'index']);
    Route::post('/create-payment', [TestKhqrController::class, 'createPayment']);
    Route::post('/generate', [TestKhqrController::class, 'generateQR']);
    Route::post('/status', [TestKhqrController::class, 'checkStatus']);
});
