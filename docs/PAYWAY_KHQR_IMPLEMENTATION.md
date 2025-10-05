# PayWay KHQR Integration - Implementation Guide

## Overview

This document describes the PayWay KHQR (ABA Bakong QR) integration for the School Registration System. The implementation allows students to pay registration fees and other payments using ABA's KHQR payment method.

## Architecture

### Database Structure

#### 1. `payments` table (existing)
- Stores all payment records
- Links to students
- Contains payment status, amount, etc.

#### 2. `payway_transactions` table
- Stores PayWay-specific transaction data
- Links to payments table
- Contains QR code data, deeplinks, etc.

#### 3. `payway_pushbacks` table
- Stores webhook callbacks from PayWay
- Logs all webhook events for debugging

### Models

1. **Payment** (`app/Models/Payment.php`)
   - Existing model with relationship to PaywayTransaction

2. **PaywayTransaction** (`app/Models/PaywayTransaction.php`)
   - Manages PayWay transaction lifecycle
   - Methods: `markAsSuccess()`, `markAsFailed()`, `isExpired()`

3. **PaywayPushback** (`app/Models/PaywayPushback.php`)
   - Stores webhook data
   - Method: `getReturnParameters()`

### Services

**PaywayService** (`app/Services/PaywayService.php`)
- `generateKHQR()` - Generates KHQR for payment
- `checkTransactionStatus()` - Checks payment status with PayWay
- Hash generation methods for security

### Controllers

**PaymentController** (`app/Http/Controllers/API/PaymentController.php`)
- `generateKHQR()` - API endpoint to generate QR
- `webhook()` - Handles PayWay callbacks
- `checkStatus()` - Check payment status

## API Endpoints

### 1. Generate KHQR
```
POST /api/payway/khqr/generate
Authorization: Bearer {token}

Request Body:
{
    "payment_uuid": "uuid-here",
    "first_name": "John",      // optional
    "last_name": "Doe",         // optional
    "email": "john@email.com",  // optional
    "phone": "0123456789"       // optional
}

Response:
{
    "success": true,
    "data": {
        "transaction_uuid": "...",
        "qr_string": "base64_qr_data",
        "qr_url": "url_to_qr_image",
        "deeplink": "abapay://...",
        "expires_at": "2025-10-05 10:30:00",
        "payment_code": "PAY202510-0001"
    }
}
```

### 2. Check Payment Status
```
POST /api/payway/payment/status
Authorization: Bearer {token}

Request Body:
{
    "payment_uuid": "uuid-here"
}

Response:
{
    "success": true,
    "data": {
        "payment_uuid": "...",
        "payment_code": "PAY202510-0001",
        "status": "paid",
        "amount": "100.00",
        "paid_at": "2025-10-05 10:15:00",
        "transaction": {
            "status": "success",
            "qr_url": "...",
            "deeplink": "...",
            "expires_at": "..."
        }
    }
}
```

### 3. Webhook (PayWay Callback)
```
POST /api/payway/webhook

This endpoint receives callbacks from PayWay.
No authentication required.
Always returns: {"status": "success"}
```

## Payment Flow

### 1. Student Completes Registration
```php
// After student fills in registration form
// Create a payment record
$payment = Payment::create([
    'student_id' => $student->id,
    'amount' => 100.00,
    'payment_type' => 'registration_fee',
    'status' => 'pending',
    // ... other fields
]);
```

### 2. Generate KHQR
```javascript
// Frontend calls API
const response = await fetch('/api/payway/khqr/generate', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        payment_uuid: payment.uuid,
        first_name: student.first_name,
        last_name: student.last_name,
        phone: student.phone
    })
});

const data = await response.json();
// Display QR code: data.data.qr_url
// Or deeplink for mobile: data.data.deeplink
```

### 3. Student Scans & Pays
- Student scans QR with ABA app
- Or clicks deeplink on mobile
- Completes payment in ABA app

### 4. PayWay Sends Webhook
- PayWay automatically calls `/api/payway/webhook`
- System updates payment status
- Transaction marked as success/failed

### 5. Frontend Polls Status (Optional)
```javascript
// Poll every 3 seconds
setInterval(async () => {
    const response = await fetch('/api/payway/payment/status', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            payment_uuid: payment.uuid
        })
    });

    const data = await response.json();
    if (data.data.status === 'paid') {
        // Show success message
        // Redirect to success page
    }
}, 3000);
```

## Configuration

### Environment Variables (.env)
```env
PAYWAY_API_KEY=your_api_key
PAYWAY_MERCHANT_ID=your_merchant_id
PAYWAY_API_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase
PAYWAY_CHECK_TRANSACTION_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction
```

### Config File (config/payway.php)
```php
return [
    'api_key' => env('PAYWAY_API_KEY'),
    'merchant_id' => env('PAYWAY_MERCHANT_ID'),
    'api_url' => env('PAYWAY_API_URL'),
    'check_transaction_api_url' => env('PAYWAY_CHECK_TRANSACTION_URL'),
    'log_all_events' => env('PAYWAY_LOG_ALL_EVENTS', true),

    'khqr' => [
        'payment_option_code' => 'khqr',
        'qr_expiry_minutes' => 15,
    ],
];
```

## Security

### Hash Generation
All requests to PayWay are secured with HMAC SHA-512 hash:

```php
$dataToHash = $reqTime . $merchantId . $transactionId . $amount .
              $items . $shipping . $firstName . $lastName . $email .
              $phone . $paymentOption . $returnUrl . $continueUrl .
              $returnDeeplink . $returnParams;

$hash = base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
```

### Webhook Verification
- Return parameters are base64 encoded
- Contains transaction and payment UUIDs
- Validated before processing

## Testing

### 1. Test KHQR Generation
```bash
curl -X POST http://localhost:8000/api/payway/khqr/generate \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_uuid": "payment-uuid-here",
    "first_name": "Test",
    "last_name": "User",
    "phone": "0123456789"
  }'
```

### 2. Test Webhook Locally
```bash
curl -X POST http://localhost:8000/api/payway/webhook \
  -H "Content-Type: application/json" \
  -d '{
    "tran_id": "PAY202510-0001",
    "status": "0",
    "apv": "123456",
    "return_params": "base64_encoded_params"
  }'
```

### 3. Check Payment Status
```bash
curl -X POST http://localhost:8000/api/payway/payment/status \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "payment_uuid": "payment-uuid-here"
  }'
```

## Logging

All PayWay interactions are logged:
- KHQR generation attempts
- API responses
- Webhook callbacks
- Payment status updates
- Errors and exceptions

Check logs at: `storage/logs/laravel.log`

## Production Checklist

- [ ] Update PayWay URLs to production endpoints
- [ ] Configure production API credentials
- [ ] Set up proper webhook URL (must be HTTPS)
- [ ] Test webhook with PayWay
- [ ] Enable error monitoring (Sentry, etc.)
- [ ] Set up email notifications for payment success/failure
- [ ] Configure payment timeout handling
- [ ] Test QR expiry behavior
- [ ] Set up database backups
- [ ] Configure queue workers for async processing (optional)

## Troubleshooting

### QR Code Not Generating
1. Check PayWay credentials in `.env`
2. Verify API URL is correct
3. Check logs for API errors
4. Ensure payment record exists

### Webhook Not Working
1. Verify webhook URL is accessible (use ngrok for local testing)
2. Check PayWay dashboard webhook configuration
3. Review webhook logs
4. Ensure return_params are properly encoded

### Payment Status Not Updating
1. Check if webhook was received (check logs)
2. Verify transaction and payment UUIDs match
3. Check database for pushback records
4. Review error logs for exceptions

## Next Steps (Optional Enhancements)

1. **State Machine** - Implement Spatie Laravel Model States for better state management
2. **Queue Processing** - Move webhook processing to queues
3. **Notifications** - Send email/SMS on payment success
4. **Retry Logic** - Handle failed payments with retry mechanism
5. **Admin Dashboard** - Build admin panel to monitor payments
6. **Refunds** - Implement refund functionality
7. **Reports** - Generate payment reports and analytics

## Support

For PayWay API issues:
- Documentation: https://payway.com.kh/developers
- Support: support@payway.com.kh

For implementation questions:
- Check logs: `storage/logs/laravel.log`
- Review this documentation
- Check PayWay webhook logs in database
