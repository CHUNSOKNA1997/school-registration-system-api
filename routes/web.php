<?php

use Illuminate\Support\Facades\Route;
use App\Models\Payment;

// Route::get('/', function () {
//     return view('welcome');
// });

// Payment Checkout Page
Route::get('/payment/{payment:uuid}/checkout', function (Payment $payment) {
    // Get the latest transaction for this payment
    $transaction = $payment->paywayTransaction;

    return view('payment.checkout', [
        'payment' => $payment,
        'qrImage' => $transaction?->qr_url,
        'deeplink' => $transaction?->deeplink,
    ]);
})->name('payment.checkout');

// TEST PAGE - Remove this in production!
Route::get('/test-payment', function () {
    return view('test-payment');
});

// PayWay Hosted Checkout Page - Redirect to PayWay's checkout URL
Route::get('/payway/checkout/{payment:uuid}', function (Payment $payment) {
    $service = new App\Services\PaywayService();
    $result = $service->generateKHQR($payment, [
        'first_name' => $payment->student->first_name ?? 'Customer',
        'last_name' => $payment->student->last_name ?? '',
        'email' => $payment->student->email ?? '',
        'phone' => $payment->student->phone ?? '',
    ]);

    if (!$result['success']) {
        abort(500, 'Failed to generate payment');
    }

    // Redirect to PayWay's hosted checkout URL (constructed by PaywayService like Sakal does)
    return redirect($result['checkout_qr_url']);
})->name('payway.hosted.checkout');
