<?php

return [
    'name' => 'Payway',

    'api_key' => env('PAYWAY_API_KEY', ''),
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_url' => env('PAYWAY_API_URL', ''),
    'check_transaction_api_url' => env('PAYWAY_CHECK_TRANSACTION_URL', ''),

    'log_all_events' => env('PAYWAY_LOG_ALL_EVENTS', true),

    // KHQR specific settings
    'khqr' => [
        'payment_option_code' => 'khqr', // or 'KHQR' - check with PayWay docs
        'qr_expiry_minutes' => 15, // QR code validity
    ],
];
