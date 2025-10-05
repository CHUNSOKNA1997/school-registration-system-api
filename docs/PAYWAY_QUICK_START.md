# PayWay KHQR - Quick Start Guide

## ✅ What's Been Implemented

### Database Tables
- ✅ `payway_transactions` - Stores QR transactions
- ✅ `payway_pushbacks` - Stores webhook callbacks
- ✅ `payments` - Existing table (updated with relationships)

### Models
- ✅ `PaywayTransaction` - Transaction management
- ✅ `PaywayPushback` - Webhook data
- ✅ `Payment` - Updated with PayWay relationship

### Services
- ✅ `PaywayService` - Core KHQR logic

### API Endpoints
- ✅ `POST /api/payway/khqr/generate` - Generate KHQR
- ✅ `POST /api/payway/payment/status` - Check payment status
- ✅ `POST /api/payway/webhook` - Webhook handler

## 🚀 How to Use

### 1. Create a Payment
```php
use App\Models\Payment;

$payment = Payment::create([
    'student_id' => $studentId,
    'amount' => 100.00,
    'payment_type' => 'registration_fee',
    'payment_period' => 'one_time',
    'payment_method' => 'KHQR',
    'due_date' => now()->addDays(7),
    'status' => 'pending',
    'description' => 'Student Registration Fee',
]);
```

### 2. Generate KHQR (API Call)
```bash
POST /api/payway/khqr/generate
Authorization: Bearer {your-token}
Content-Type: application/json

{
    "payment_uuid": "{{payment-uuid}}",
    "first_name": "Sophea",
    "last_name": "Chan",
    "phone": "012345678"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "transaction_uuid": "...",
        "qr_url": "https://...",
        "deeplink": "abapay://...",
        "expires_at": "2025-10-05 10:30:00"
    }
}
```

### 3. Display QR Code (Frontend)
```html
<!-- Display QR Image -->
<img src="{{ qr_url }}" alt="Scan to Pay" />

<!-- Or Mobile Deeplink -->
<a href="{{ deeplink }}">Pay with ABA</a>

<!-- Timer -->
<p>Expires in: <span id="timer"></span></p>
```

### 4. Poll Payment Status (Frontend)
```javascript
// Check status every 3 seconds
const interval = setInterval(async () => {
    const response = await fetch('/api/payway/payment/status', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            payment_uuid: paymentUuid
        })
    });

    const data = await response.json();

    if (data.data.status === 'paid') {
        clearInterval(interval);
        alert('Payment successful!');
        window.location.href = '/success';
    }
}, 3000);
```

## 🔧 Configuration

Your `.env` already has:
```env
PAYWAY_API_KEY=44f179ec927c49f3a36d80e3c277643d0cb52caa
PAYWAY_MERCHANT_ID=ec461864
PAYWAY_API_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase
PAYWAY_CHECK_TRANSACTION_URL=https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction
```

## 📋 Integration with Student Registration

### Option 1: During Registration (Step 8)
```php
// In your registration controller (Step 8)
public function completeRegistration(Request $request)
{
    // ... validate and save student data (steps 1-7)

    // Create payment
    $payment = Payment::create([
        'student_id' => $student->id,
        'amount' => 100.00, // registration fee
        'payment_type' => 'registration_fee',
        'status' => 'pending',
        // ... other fields
    ]);

    // Return payment UUID to frontend
    return response()->json([
        'success' => true,
        'student' => $student,
        'payment_uuid' => $payment->uuid,
        'next_step' => 'payment' // Tell frontend to show payment page
    ]);
}
```

### Option 2: Separate Payment Page
```php
// After registration, redirect to payment page
Route::get('/students/{student}/payment', function (Student $student) {
    // Find or create pending payment
    $payment = $student->payments()->pending()->first();

    if (!$payment) {
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 100.00,
            'status' => 'pending',
            // ...
        ]);
    }

    return view('payment.page', [
        'student' => $student,
        'payment_uuid' => $payment->uuid
    ]);
});
```

## 🧪 Testing

### 1. Test with Postman

**Step 1: Login/Get Token**
```bash
POST /api/login
{
    "email": "admin@example.com",
    "password": "password"
}
```

**Step 2: Create Payment** (or use existing)
```bash
POST /api/payments
{
    "student_id": 1,
    "amount": 100.00,
    ...
}
```

**Step 3: Generate KHQR**
```bash
POST /api/payway/khqr/generate
Authorization: Bearer {token}
{
    "payment_uuid": "{{uuid}}"
}
```

**Step 4: Simulate Webhook** (for testing)
```bash
POST /api/payway/webhook
{
    "tran_id": "PAY202510-0001",
    "status": "0",
    "apv": "123456",
    "return_params": "eyJ0cmFuc2FjdGlvbl91dWlkIjoiLi4uIiwicGF5bWVudF91dWlkIjoiLi4uIn0="
}
```

### 2. Test Locally with ngrok

PayWay needs a public URL for webhooks. Use ngrok:

```bash
# Install ngrok
# Start ngrok
ngrok http 8000

# Copy ngrok URL
# Update PayWay dashboard webhook URL to:
https://your-ngrok-url.ngrok.io/api/payway/webhook
```

## 📊 Monitoring

### Check Logs
```bash
tail -f storage/logs/laravel.log | grep -i payway
```

### Check Database
```sql
-- Check transactions
SELECT * FROM payway_transactions ORDER BY created_at DESC LIMIT 10;

-- Check webhooks
SELECT * FROM payway_pushbacks ORDER BY created_at DESC LIMIT 10;

-- Check payments
SELECT * FROM payments WHERE status = 'paid' ORDER BY paid_at DESC LIMIT 10;
```

## 🐛 Common Issues

### Issue 1: QR Not Generating
**Solution:**
- Check PayWay credentials
- Verify API URL
- Check `storage/logs/laravel.log`

### Issue 2: Webhook Not Received
**Solution:**
- Use ngrok for local testing
- Verify webhook URL in PayWay dashboard
- Check if URL is publicly accessible

### Issue 3: Payment Status Not Updating
**Solution:**
- Check `payway_pushbacks` table
- Verify return_params decoding
- Check error logs

## 📝 Next Steps

### Immediate (Must Do):
1. Test KHQR generation
2. Test payment flow end-to-end
3. Configure webhook URL in PayWay dashboard

### Soon:
1. Integrate with student registration flow
2. Add email notifications
3. Build payment success/failure pages
4. Add payment history page

### Later (Optional):
1. Install Spatie Model States
2. Add queue processing
3. Build admin dashboard
4. Implement refunds

## 📚 Documentation

- Full Implementation Guide: `PAYWAY_KHQR_IMPLEMENTATION.md`
- PayWay API Docs: https://payway.com.kh/developers

## 🎉 You're Ready!

The core PayWay KHQR integration is complete. You can now:
1. Generate QR codes for payments
2. Receive webhook callbacks
3. Update payment status automatically

Start testing and integrate with your frontend! 🚀
