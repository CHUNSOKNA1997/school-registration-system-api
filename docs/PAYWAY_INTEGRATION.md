# PayWay KHQR Payment Integration Guide

Complete guide to implement PayWay KHQR payment system in your Laravel application.

## Table of Contents
- [Overview](#overview)
- [How It Works](#how-it-works)
- [Backend Implementation](#backend-implementation)
- [Frontend Implementation](#frontend-implementation)
- [Configuration](#configuration)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Webhook Handling](#webhook-handling)
- [Troubleshooting](#troubleshooting)

---

## Overview

This integration allows students to pay registration fees using PayWay's KHQR (Khmer QR) payment system. Users are redirected to PayWay's official hosted checkout page to complete payment.

**Features:**
- ✅ Generate KHQR payment links
- ✅ Redirect to PayWay's hosted checkout
- ✅ Automatic payment confirmation via webhook
- ✅ Transaction status checking

---

## How It Works

### Payment Flow Diagram

```
1. Student clicks "Pay Now" 
   ↓
2. Frontend calls: POST /api/payway/v1/khqr/generate
   ↓
3. Backend generates checkout_qr_url
   ↓
4. Frontend redirects user to checkout_qr_url
   ↓
5. User sees PayWay's official checkout page
   ↓
6. User scans QR code and pays
   ↓
7. PayWay sends webhook to: POST /api/payway/v1/webhook
   ↓
8. Backend updates payment status to "paid"
   ↓
9. User redirected back to success page
```

### Key Concept: checkout_qr_url

The `checkout_qr_url` is a special URL that displays PayWay's official checkout page. It's constructed by:
1. Calling PayWay's API to get QR data
2. Building a JSON object with transaction details
3. Base64 encoding the JSON
4. Appending to PayWay's checkout URL

Example:
```
https://checkout-sandbox.payway.com.kh/eyJ0eXAiOiJKV1QiLC...
```

---

## Backend Implementation

### Step 1: Install Required Packages

Your project already has all dependencies. If starting fresh:

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### Step 2: Database Setup

Create the required tables:

```bash
php artisan migrate
```

**Tables created:**
- `payments` - Student payment records
- `payway_transactions` - PayWay transaction tracking
- `payway_pushbacks` - Webhook callback logs

### Step 3: Configure Environment

Add to `.env`:

```env
# PayWay Credentials
PAYWAY_MERCHANT_ID=your_merchant_id_here
PAYWAY_API_KEY=your_api_key_here

# Sandbox URLs (for testing)
PAYWAY_API_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase
PAYWAY_QR_API_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/generate-qr
PAYWAY_CHECK_TRANSACTION_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction

# Production URLs (when going live)
# PAYWAY_API_URL=https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase
# PAYWAY_QR_API_URL=https://checkout.payway.com.kh/api/payment-gateway/v1/payments/generate-qr
# PAYWAY_CHECK_TRANSACTION_URL=https://checkout.payway.com.kh/api/payment-gateway/v1/payments/check-transaction

# For local development (optional - for webhook testing)
NGROK_URL=
```

### Step 4: Core Service Implementation

The main logic is in `app/Services/PaywayService.php`. Key method:

```php
public function generateKHQR(Payment $payment, array $customerData = []): array
```

**What it does:**
1. Creates/updates PaywayTransaction record
2. Prepares payment data (amount, items, customer info)
3. Generates HMAC-SHA512 hash for security
4. Calls PayWay's purchase API
5. Constructs `checkout_qr_url` by encoding transaction data
6. Returns all payment URLs

**Important Hash Calculation:**

The hash ensures request integrity. It's calculated as:
```
base64_encode(
  hash_hmac('sha512', $dataToHash, $apiKey, true)
)
```

Where `$dataToHash` includes (in exact order):
- reqTime, merchantId, tranId, amount, items, shipping
- firstName, lastName, email, phone, type, paymentOption
- callbackUrl, cancelUrl, continueUrl, returnDeeplink
- currency, customFields, returnParams, payout, lifetime
- additionalParams, googlePayToken

**Never change the order or hash will fail!**

### Step 5: Controller Implementation

File: `app/Http/Controllers/API/PaymentController.php`

**Generate KHQR endpoint:**

```php
public function generateKHQR(Request $request)
{
    $validated = $request->validate([
        'payment_uuid' => 'required|uuid|exists:payments,uuid',
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'email' => 'nullable|email',
        'phone' => 'required|string',
    ]);

    $payment = Payment::where('uuid', $validated['payment_uuid'])->firstOrFail();

    $service = new PaywayService();
    $result = $service->generateKHQR($payment, [
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'] ?? '',
        'phone' => $validated['phone'],
    ]);

    return response()->json($result);
}
```

**Webhook endpoint:**

```php
public function webhook(Request $request)
{
    DB::beginTransaction();
    
    try {
        // Log webhook
        $pushback = PaywayPushback::create([
            'tran_id' => $request->tran_id,
            'apv' => $request->apv,
            'status' => $request->status,
            'return_params' => $request->return_params,
            'data' => $request->all(),
        ]);

        // Extract payment UUIDs from return_params
        $returnParams = json_decode(base64_decode($request->return_params), true);
        
        $transaction = PaywayTransaction::where('uuid', $returnParams['transaction_uuid'])->firstOrFail();
        $payment = Payment::where('uuid', $returnParams['payment_uuid'])->firstOrFail();

        // Check if successful (status code "00" = success)
        if ($request->status === '00') {
            $transaction->markAsSuccess($request->apv, $pushback);
            
            $payment->update([
                'status' => 'paid',
                'khqr_reference' => $request->apv,
                'paid_at' => now(),
                'payment_method' => 'KHQR',
            ]);
        }

        DB::commit();
        return response()->json(['success' => true]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

### Step 6: Routes Setup

File: `routes/api.php`

```php
use App\Http\Controllers\API\PaymentController;

Route::group(['prefix' => 'payway/v1'], function () {
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/khqr/generate', [PaymentController::class, 'generateKHQR']);
        Route::post('/payment/status', [PaymentController::class, 'checkStatus']);
    });

    // Webhook (public - PayWay calls this)
    Route::post('/webhook', [PaymentController::class, 'webhook']);
});
```

---

## Frontend Implementation

### Step 1: Create Payment UI

```html
<!-- Example: Payment button -->
<button id="payNowBtn" onclick="initiatePayment()">
  Pay Now - $150.00
</button>
```

### Step 2: Payment Initiation Script

```javascript
async function initiatePayment() {
    const paymentUuid = 'YOUR_PAYMENT_UUID'; // From your payment record
    const authToken = localStorage.getItem('auth_token'); // Your auth token

    try {
        // Show loading state
        document.getElementById('payNowBtn').disabled = true;
        document.getElementById('payNowBtn').innerText = 'Processing...';

        // Call backend to generate checkout URL
        const response = await fetch('/api/payway/v1/khqr/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${authToken}`
            },
            body: JSON.stringify({
                payment_uuid: paymentUuid,
                first_name: 'John',
                last_name: 'Doe',
                email: 'john@example.com',
                phone: '012345678'
            })
        });

        const data = await response.json();

        if (data.success && data.checkout_qr_url) {
            // Redirect to PayWay's hosted checkout
            window.location.href = data.checkout_qr_url;
        } else {
            alert('Failed to generate payment: ' + (data.message || 'Unknown error'));
        }

    } catch (error) {
        console.error('Payment error:', error);
        alert('Payment failed. Please try again.');
    } finally {
        document.getElementById('payNowBtn').disabled = false;
        document.getElementById('payNowBtn').innerText = 'Pay Now';
    }
}
```

### Step 3: React/Vue Example

**React:**

```jsx
import { useState } from 'react';
import axios from 'axios';

function PaymentButton({ paymentUuid, studentInfo }) {
    const [loading, setLoading] = useState(false);

    const handlePayment = async () => {
        setLoading(true);
        
        try {
            const response = await axios.post('/api/payway/v1/khqr/generate', {
                payment_uuid: paymentUuid,
                first_name: studentInfo.firstName,
                last_name: studentInfo.lastName,
                email: studentInfo.email,
                phone: studentInfo.phone
            }, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.data.success) {
                // Redirect to PayWay checkout
                window.location.href = response.data.checkout_qr_url;
            }
        } catch (error) {
            console.error('Payment failed:', error);
            alert('Payment failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <button 
            onClick={handlePayment} 
            disabled={loading}
            className="btn btn-primary"
        >
            {loading ? 'Processing...' : 'Pay Now'}
        </button>
    );
}
```

**Vue:**

```vue
<template>
  <button @click="initiatePayment" :disabled="loading">
    {{ loading ? 'Processing...' : 'Pay Now' }}
  </button>
</template>

<script>
export default {
  props: ['paymentUuid', 'studentInfo'],
  data() {
    return {
      loading: false
    }
  },
  methods: {
    async initiatePayment() {
      this.loading = true;
      
      try {
        const response = await this.$axios.post('/api/payway/v1/khqr/generate', {
          payment_uuid: this.paymentUuid,
          first_name: this.studentInfo.firstName,
          last_name: this.studentInfo.lastName,
          email: this.studentInfo.email,
          phone: this.studentInfo.phone
        });

        if (response.data.success) {
          window.location.href = response.data.checkout_qr_url;
        }
      } catch (error) {
        alert('Payment failed. Please try again.');
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
```

### Step 4: Success/Failure Pages

Create pages to handle redirects after payment:

```php
// In routes/web.php or your frontend routing

// Success page
Route::get('/payment/success', function() {
    return view('payment.success');
});

// Failure page  
Route::get('/payment/failed', function() {
    return view('payment.failed');
});
```

---

## Configuration

### Environment Variables Explained

| Variable | Description | Example |
|----------|-------------|---------|
| `PAYWAY_MERCHANT_ID` | Your PayWay merchant ID | `merchantABC123` |
| `PAYWAY_API_KEY` | Secret API key for hash generation | `your-secret-key` |
| `PAYWAY_API_URL` | Purchase endpoint URL | See above |
| `PAYWAY_QR_API_URL` | QR generation endpoint | See above |
| `PAYWAY_CHECK_TRANSACTION_URL` | Status check endpoint | See above |
| `NGROK_URL` | For local webhook testing | `https://abc.ngrok.io` |

### Config File

File: `config/payway.php`

```php
return [
    'merchant_id' => env('PAYWAY_MERCHANT_ID', ''),
    'api_key' => env('PAYWAY_API_KEY', ''),
    'api_url' => env('PAYWAY_API_URL'),
    'qr_api_url' => env('PAYWAY_QR_API_URL'),
    'check_transaction_api_url' => env('PAYWAY_CHECK_TRANSACTION_URL'),
    
    'khqr' => [
        'payment_option_code' => 'abapay',
        'qr_expiry_minutes' => 15,
    ],
];
```

---

## API Endpoints

### 1. Generate KHQR Payment

**POST** `/api/payway/v1/khqr/generate`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

**Request:**
```json
{
  "payment_uuid": "b2c24e36-76f2-41e0-bd5d-d5da559ff03a",
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "012345678"
}
```

**Response:**
```json
{
  "success": true,
  "transaction_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "qr_string": "00020101021...",
  "qr_url": "https://checkout-sandbox.payway.com.kh/qr/image.png",
  "deeplink": "abapay://payment?data=...",
  "checkout_qr_url": "https://checkout-sandbox.payway.com.kh/eyJzdGF0dXMi...",
  "expires_at": "2025-10-06 15:30:00",
  "payment_code": "PAY-ABC12345"
}
```

**Key Response Fields:**
- `checkout_qr_url` ⭐ - **Main URL to redirect user to**
- `qr_string` - Raw QR data (optional use)
- `deeplink` - ABA Pay app link (optional use)

### 2. Check Payment Status

**POST** `/api/payway/v1/payment/status`

**Request:**
```json
{
  "tran_id": "PAY-ABC12345"
}
```

**Response:**
```json
{
  "status": "00",
  "message": "Success",
  "tran_id": "PAY-ABC12345",
  "amount": "150.00"
}
```

### 3. Webhook (PayWay → Your Server)

**POST** `/api/payway/v1/webhook`

PayWay automatically calls this when payment is complete.

**Payload:**
```json
{
  "tran_id": "PAY-ABC12345",
  "apv": "APV123456789",
  "status": "00",
  "status_message": "Success",
  "return_params": "eyJ0cmFuc2FjdGlvbl91dWlkIjoi..."
}
```

**Status Codes:**
- `00` = Success ✅
- `01` = Pending
- `99` = Failed ❌

---

## Testing

### 1. Setup Local Development

```bash
# Start Laravel server
php artisan serve

# In another terminal, start ngrok
ngrok http 8000

# Copy ngrok URL and add to .env
NGROK_URL=https://abc123.ngrok.io
```

### 2. Create Test Payment

```bash
php artisan tinker
```

```php
$payment = \App\Models\Payment::create([
    'uuid' => \Illuminate\Support\Str::uuid(),
    'payment_code' => 'PAY-' . strtoupper(\Illuminate\Support\Str::random(8)),
    'student_id' => 1,
    'academic_year' => '2024-2025',
    'payment_type' => 'tuition',
    'payment_period' => 'monthly',
    'amount' => 150.00,
    'total_amount' => 150.00,
    'payment_month' => 'October',
    'due_date' => '2025-10-15',
    'status' => 'pending',
    'description' => 'Test tuition payment'
]);

// Copy this UUID
echo $payment->uuid;
```

### 3. Test API Call

```bash
curl -X POST http://localhost:8000/api/payway/v1/khqr/generate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "payment_uuid": "YOUR_PAYMENT_UUID",
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "phone": "012345678"
  }'
```

### 4. Test Checkout Flow

1. Copy `checkout_qr_url` from response
2. Open in browser
3. You'll see PayWay's checkout page
4. Use PayWay sandbox test credentials

### 5. Test Webhook

PayWay will automatically call your webhook. Check logs:

```bash
tail -f storage/logs/laravel.log
```

---

## Webhook Handling

### Why Webhooks?

PayWay uses webhooks to notify your server when payment is completed. Your server must be publicly accessible.

### Local Development (ngrok)

```bash
# Install ngrok
brew install ngrok  # macOS
# or download from https://ngrok.com

# Start ngrok
ngrok http 8000

# Output will show:
# Forwarding: https://abc123.ngrok.io -> http://localhost:8000

# Add to .env
NGROK_URL=https://abc123.ngrok.io
```

### How Webhook Works

1. User completes payment on PayWay
2. PayWay sends POST to: `{NGROK_URL}/api/payway/v1/webhook`
3. Your server receives:
   - `tran_id` - Transaction ID
   - `apv` - Payment reference
   - `status` - "00" for success
   - `return_params` - Base64 encoded data with UUIDs
4. Server updates payment status
5. User sees success message

### Webhook Security

The `return_params` contains your payment and transaction UUIDs:

```php
$returnParams = base64_decode($request->return_params);
// Contains: {"transaction_uuid": "...", "payment_uuid": "..."}
```

This ensures the webhook is for the correct payment.

---

## Troubleshooting

### Issue 1: "Invalid hash" error

**Cause:** Hash calculation is incorrect

**Solution:**
- Verify `PAYWAY_API_KEY` is correct
- Ensure no extra spaces in .env
- Check hash parameter order (must match exactly)
- Don't modify `generateHashForKHQR()` method

### Issue 2: Webhook not received

**Cause:** Server not publicly accessible

**Solution:**
- For local: Ensure ngrok is running
- Check `NGROK_URL` in .env
- Verify webhook endpoint: `/api/payway/v1/webhook`
- Check PayWay dashboard for webhook delivery status

### Issue 3: checkout_qr_url shows error

**Cause:** Invalid payment data or token

**Solution:**
- Verify amount > 0
- Check merchant_id is correct
- Ensure all required fields are provided
- Decode the token to see the data:
  ```php
  $token = 'eyJ...'; // from checkout_qr_url
  $data = json_decode(base64_decode($token), true);
  dd($data); // Check for errors
  ```

### Issue 4: Payment status not updating

**Cause:** Webhook failed or not processed

**Solution:**
- Check logs: `storage/logs/laravel.log`
- Verify database transaction commit
- Check `return_params` decoding
- Ensure UUIDs match

### Issue 5: 401 Unauthorized

**Cause:** Missing or invalid auth token

**Solution:**
- Include `Authorization: Bearer {token}` header
- Verify Sanctum is configured
- Check token hasn't expired

---

## Production Checklist

Before deploying to production:

### Backend
- [ ] Update API URLs to production endpoints (remove `-sandbox`)
- [ ] Set `PAYWAY_LOG_ALL_EVENTS=false`
- [ ] Remove `NGROK_URL` from .env
- [ ] Configure SSL certificate (HTTPS required)
- [ ] Set up proper error monitoring
- [ ] Test webhook on production domain
- [ ] Configure rate limiting on API routes

### Frontend
- [ ] Update API base URL to production
- [ ] Test payment flow end-to-end
- [ ] Add proper error messages
- [ ] Implement loading states
- [ ] Add payment confirmation page
- [ ] Test on mobile devices

### Security
- [ ] Validate all user inputs
- [ ] Use HTTPS everywhere
- [ ] Keep API keys secure (never expose in frontend)
- [ ] Implement CSRF protection
- [ ] Set up webhook signature verification (if PayWay provides)

### Monitoring
- [ ] Set up webhook failure alerts
- [ ] Monitor payment success rates
- [ ] Log all payment attempts
- [ ] Track failed transactions
- [ ] Set up database backups

---

## Code Structure

```
app/
├── Http/
│   └── Controllers/
│       └── API/
│           └── PaymentController.php       # API endpoints
├── Services/
│   ├── PaywayService.php                   # Core payment logic
│   └── PaywayCallbackService.php           # Webhook URL handling
└── Models/
    ├── Payment.php                          # Payment records
    ├── PaywayTransaction.php                # Transaction tracking
    └── PaywayPushback.php                   # Webhook logs

config/
└── payway.php                               # PayWay configuration

routes/
└── api.php                                  # API routes

database/
└── migrations/
    ├── xxxx_create_payments_table.php
    ├── xxxx_create_payway_transactions_table.php
    └── xxxx_create_payway_pushbacks_table.php
```

### Key Methods

**PaywayService.php:**
- `generateKHQR()` - Main method to create payment
- `generateHashForKHQR()` - Security hash generation
- `callPaywayAPI()` - API communication
- `checkTransactionStatus()` - Check payment status

**PaymentController.php:**
- `generateKHQR()` - API endpoint for payment generation
- `webhook()` - Receives PayWay callbacks
- `checkStatus()` - Check transaction status

---

## Support & Resources

- **PayWay Developer Docs:** https://developer.payway.com.kh/
- **Laravel Sanctum:** https://laravel.com/docs/sanctum
- **ngrok:** https://ngrok.com/docs

---

## Quick Start Summary

1. **Configure .env** with PayWay credentials
2. **Run migrations** to create tables
3. **Create a payment** record in database
4. **Call API** `/api/payway/v1/khqr/generate` with payment UUID
5. **Redirect user** to `checkout_qr_url` from response
6. **User pays** on PayWay's hosted page
7. **Webhook updates** payment status automatically
8. **Done!** Payment is complete

---

## Example: Complete Payment Flow

```javascript
// 1. Student clicks Pay button
document.getElementById('payBtn').addEventListener('click', async () => {
    
    // 2. Call your API
    const response = await fetch('/api/payway/v1/khqr/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + authToken
        },
        body: JSON.stringify({
            payment_uuid: paymentId,
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            phone: '012345678'
        })
    });
    
    const data = await response.json();
    
    // 3. Redirect to PayWay
    if (data.success) {
        window.location.href = data.checkout_qr_url;
    }
});

// 4. User completes payment on PayWay
// 5. PayWay sends webhook to your server
// 6. Your server updates payment status
// 7. User redirected back to your success page
// 8. Show payment confirmation!
```

---

**That's it! You now have a complete PayWay KHQR integration.** 🎉

For questions or issues, refer to the troubleshooting section or contact your development team.
