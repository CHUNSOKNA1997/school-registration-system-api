<?php

return [
    'name' => 'Payway',

    'api_key' => env('PAYWAY_API_KEY', ''),
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_url' => env('PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
    'qr_api_url' => env('PAYWAY_QR_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/generate-qr'),
    'check_transaction_api_url' => env('PAYWAY_CHECK_TRANSACTION_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction'),

    'log_all_events' => env('PAYWAY_LOG_ALL_EVENTS', true),

    // KHQR specific settings
    'khqr' => [
        'payment_option_code' => 'abapay', // Options: 'khqr', 'abapay', 'bakong', 'cards', 'alipay', 'wechat'
        'qr_expiry_minutes' => 15, // QR code validity
        // QR image templates: template1, template2, template3, template3_color, template4, template4_color
        'qr_image_template' => env('PAYWAY_QR_TEMPLATE', 'template3_color'),
    ],
];
