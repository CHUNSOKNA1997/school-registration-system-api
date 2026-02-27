<?php

return [
    'name' => 'Payway',

    'api_key' => env('PAYWAY_API_KEY', ''),
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_url' => env('PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
    'qr_api_url' => env('PAYWAY_QR_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/generate-qr'),
    'check_transaction_api_url' => env('PAYWAY_CHECK_TRANSACTION_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction'),
    'checkout_url' => env('PAYWAY_CHECKOUT_URL', 'https://checkout-sandbox.payway.com.kh'),
    // Options: unix | ymdhis
    'req_time_format' => env('PAYWAY_REQ_TIME_FORMAT', 'ymdhis'),

    'log_all_events' => env('PAYWAY_LOG_ALL_EVENTS', true),

    // KHQR specific settings
    'khqr' => [
        // API mode:
        // - 'qr' uses /payments/generate-qr and expects JSON qrString/qrImage
        // - 'purchase' uses hosted checkout form flow (sample-compatible)
        'api_mode' => env('PAYWAY_KHQR_API_MODE', 'purchase'),
        'payment_option_code' => 'abapay', // Options: 'khqr', 'abapay', 'bakong', 'cards', 'alipay', 'wechat'
        'qr_expiry_minutes' => 15, // QR code validity
        // QR image templates: template1, template2, template3, template3_color, template4, template4_color
        'qr_image_template' => env('PAYWAY_QR_TEMPLATE', 'template3_color'),
    ],

    'webhook' => [
        // If true, webhook payload must include a valid signature header.
        'require_signature' => env('PAYWAY_WEBHOOK_REQUIRE_SIGNATURE', false),
        'signature_header' => env('PAYWAY_WEBHOOK_SIGNATURE_HEADER', 'X-PayWay-Signature'),
        // If true, validate success webhooks against PayWay check-transaction API.
        'verify_with_check_transaction' => env('PAYWAY_WEBHOOK_VERIFY_WITH_CHECK_TRANSACTION', true),
    ],

    // Response shaping to avoid very large payloads.
    'response' => [
        'include_raw_qr' => env('PAYWAY_RESPONSE_INCLUDE_RAW_QR', false), // qr_string/qr_url/deeplink
        'include_checkout_qr_url' => env('PAYWAY_RESPONSE_INCLUDE_CHECKOUT_QR_URL', false),
        // Status endpoint payload shaping.
        'include_raw_qr_in_status' => env('PAYWAY_RESPONSE_INCLUDE_RAW_QR_IN_STATUS', false),
    ],
];
