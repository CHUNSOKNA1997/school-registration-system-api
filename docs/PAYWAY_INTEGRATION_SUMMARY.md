# PayWay KHQR Payment Integration - Complete Summary

## Overview

This document summarizes the implementation of PayWay ABA KHQR payment integration in the Laravel 11 school registration system, based on patterns from the Sakal project.

---

## 1. Primary Request and Intent

**Main Goal:** Implement PayWay ABA KHQR payment integration following the Sakal project pattern.

**Key User Requirements Evolution:**
1. Initially: "I only want to focus on aba khqr, which responsible on qr code generation"
2. Then: "it works now, the approach i want it I wanna rediect to payway page after successfully redirect back"
3. Later: "in sakal, it returns an iframe" - User wanted iframe modal approach like Sakal
4. Final intent: "all i want is I want payway to handle on generate qr itself, not us"

**Testing Requirement:** Create a test page to verify KHQR integration works before implementing in main application

---

## 2. Key Technical Concepts

- **PayWay Integration:** ABA Bank's payment gateway for Cambodia
- **KHQR (Khmer QR):** Cambodia's national QR code standard for payments
- **ABA Pay:** Mobile banking app payment option (alternative to KHQR)
- **HMAC SHA-512 Hash:** Security mechanism for PayWay API authentication
- **Laravel 11:** PHP framework with Blade templates, Sanctum, routing
- **Alpine.js:** Frontend JavaScript framework for interactivity
- **Tailwind CSS:** Utility-first CSS framework
- **Session Storage:** Laravel sessions for storing form data between redirects
- **Webhook/Callback:** Asynchronous payment notification from PayWay
- **NGROK:** Tunneling service for local webhook testing
- **Base64 Encoding:** Used for URLs and parameters in PayWay integration
- **Enum Validation:** Laravel enum validation for Student and Payment models
- **Hosted Checkout:** PayWay's hosted payment page approach using iframe

---

## 3. PayWay Credentials

**Merchant Details:**
- Merchant ID: `ec461864`
- API Key (for HMAC): `44f179ec927c49f3a36d80e3c277643d0cb52caa` (Valid until: 19 October 2025)
- Environment: Sandbox

**API Endpoints:**
- Payment API: `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase`
- QR API: `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/generate-qr`
- Transaction Check: `https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/check-transaction`

**Important Note:** The "Public Key" in credential_info.txt is actually the API Key used for HMAC SHA-512 hashing, not an RSA public key.

---

## 4. Critical Implementation Details

### Hash Generation (Matching Sakal's Format)

The hash must be generated using HMAC SHA-512 with ALL parameters in the exact order:

```php
private function generateHashForHostedPage(
    $reqTime, $transactionId, $amount, $items, $shipping,
    $firstName, $lastName, $email, $phone, $paymentOption,
    $returnUrl, $continueUrl, $returnParams
): string {
    $apiKey = config('payway.api_key');
    $merchantId = config('payway.merchant_id');

    // Additional parameters (empty strings as defaults)
    $type = 'purchase';
    $cancelUrl = '';
    $returnDeeplink = '';
    $currency = 'USD';
    $customFields = '';
    $payout = '';
    $lifetime = '';
    $additionalParams = '';
    $googlePayToken = '';

    // Hash format matching Sakal's PaywayManager EXACTLY
    $dataToHash = $reqTime . $merchantId . $transactionId . $amount .
                 $items . $shipping . $firstName . $lastName . $email . $phone .
                 $type . $paymentOption . $returnUrl . $cancelUrl . $continueUrl .
                 $returnDeeplink . $currency . $customFields . $returnParams . $payout .
                 $lifetime . $additionalParams . $googlePayToken;

    return base64_encode(hash_hmac('sha512', $dataToHash, $apiKey, true));
}
```

**Critical Points:**
1. All parameters must be included, even empty strings
2. Order is crucial: reqTime, merchantId, transactionId, amount, items, shipping, firstName, lastName, email, phone, type, paymentOption, returnUrl, cancelUrl, continueSuccessUrl, returnDeeplink, currency, customFields, returnParams, payout, lifetime, additionalParams, googlePayToken
3. Return URL should be base64 encoded in BOTH the hash calculation AND the form parameter
4. Use `hash_hmac('sha512', $dataToHash, $apiKey, true)` with the third parameter as `true` to get raw binary output
5. Base64 encode the final hash

### PayWay Hosted Checkout Implementation (Sakal Pattern)

Sakal uses PayWay's hosted checkout page with an iframe:

1. **Create a standalone Blade template** (like `purchase_v2.blade.php`)
2. **Include PayWay's checkout plugin:** `https://checkout.payway.com.kh/plugins/checkout2-0.js`
3. **Create a form** with all PayWay parameters as hidden inputs
4. **Submit form to iframe** using JavaScript
5. **PayWay handles the entire payment UI** including QR code generation and display

**Key Code Pattern:**
```html
<script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('.aba-modal-content').append(
            '<iframe scrolling="yes" class="aba-iframe" src="" name="aba_webservice" id="aba_webservice" style="width: 100%; min-height: 95vh;"></iframe>'
        );
        $('#aba_merchant_request').submit();
    });
</script>
```

This approach allows PayWay to handle all the payment UI, including QR code generation and display, which is what the user wanted.

---

## 5. Files Modified/Created

### `app/Services/PaywayService.php`
**Purpose:** Core service for PayWay integration

**Key Methods:**
- `generateHostedPaymentForm(Payment $payment, array $customerData = [])`: Generates form data for PayWay hosted checkout
- `generateHashForHostedPage(...)`: Generates HMAC SHA-512 hash matching Sakal's format
- `prepareItemsData(Payment $payment)`: Formats items string for PayWay

### `app/Http/Controllers/TestKhqrController.php`
**Purpose:** Controller for testing PayWay integration

**Key Methods:**
- `index()`: Display test page
- `createPayment(Request $request)`: Create test payment
- `generateQR(Request $request)`: Generate PayWay form data for checkout
- `paywayReturn()`: Handle return from PayWay
- `checkStatus(Request $request)`: Check payment status

### `routes/web.php`
Added test routes:
```php
Route::group(['prefix' => 'test/khqr'], function () {
    Route::get('/', [TestKhqrController::class, 'index'])->name('test.khqr.index');
    Route::post('/create-payment', [TestKhqrController::class, 'createPayment']);
    Route::match(['get', 'post'], '/generate', [TestKhqrController::class, 'generateQR']);
    Route::get('/payway-redirect/{payment_uuid}', [TestKhqrController::class, 'paywayRedirect'])->name('test.khqr.payway-redirect');
    Route::get('/payway-return', [TestKhqrController::class, 'paywayReturn'])->name('test.khqr.payway-return');
    Route::post('/status', [TestKhqrController::class, 'checkStatus']);
});
```

### `resources/views/test/khqr.blade.php`
**Purpose:** Main test page with Alpine.js for payment creation and PayWay checkout

**Key Features:**
- Create test payments
- Open PayWay checkout in modal with iframe
- Check payment status
- Display payment details

### `resources/views/test/payway-checkout.blade.php`
**Purpose:** Standalone PayWay hosted checkout page (following Sakal pattern)

**Implementation:**
- Form with all PayWay parameters
- Iframe for PayWay checkout
- Auto-submit form on page load
- PayWay's checkout2-0.js plugin handles the UI

### `config/payway.php`
**Key Configuration:**
```php
return [
    'merchant_id' => env('PAYWAY_MERCHANT_ID'),
    'api_key' => env('PAYWAY_API_KEY'),
    'api_url' => env('PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'),
    'khqr' => [
        'payment_option_code' => 'abapay', // Changed from 'khqr' to 'abapay'
        'qr_expiry_minutes' => 15,
    ],
];
```

### `.env`
```
PAYWAY_API_KEY="44f179ec927c49f3a36d80e3c277643d0cb52caa"
PAYWAY_MERCHANT_ID="ec461864"
PAYWAY_API_URL="https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase"
NGROK_URL="https://b0039b993df5.ngrok-free.app"
```

---

## 6. Errors Encountered and Solutions

### Error 1: "SQLSTATE[HY000]: General error: 1 no such table: sessions"
**Cause:** Sessions table missing from database
**Solution:** Created sessions migration with proper schema

### Error 2: Enum Validation Errors on Student Creation
**Cause:** Missing required enum fields (student_type, nationality, etc.)
**Solution:** Added all required enum fields to Student::firstOrCreate in TestKhqrController

### Error 3: "Selected Payment Option is not enabled" (Code 23)
**Cause:** Using 'khqr' payment option when not enabled for merchant
**Solution:** Changed payment_option_code from 'khqr' to 'abapay' in config/payway.php

### Error 4: "Wrong Hash" Error (Main Blocker)
**Causes:**
1. Wrong hash format (missing parameters)
2. Return URL encoding confusion
3. Parameter order incorrect
4. Missing empty string parameters in hash

**Critical Discovery:** The "Public Key" is actually the API Key for HMAC hashing

**Solution:**
- Match Sakal's exact hash format with all parameters
- Include empty strings for optional parameters
- Maintain exact parameter order
- Use base64 encoding for return URL

### Error 5: "The POST method is not supported for route test/khqr"
**Cause:** Route only accepted GET
**Solution:** Changed to `Route::match(['get', 'post'], '/generate', ...)`

### Error 6: Showing JSON Instead of PayWay UI
**Cause:** Attempted to use PayWay's API endpoint directly in iframe
**Understanding:** PayWay's `/api/payment-gateway/v1/payments/purchase` endpoint returns JSON, not HTML

**Solution (Sakal Pattern):**
- Use PayWay's hosted checkout page approach
- Submit form to PayWay with all parameters
- PayWay's checkout2-0.js plugin transforms the request into their hosted UI
- PayWay handles QR code generation and display in their hosted page

---

## 7. Current Implementation Status

### ✅ Completed:
1. PayWay service layer with proper hash generation
2. Test page for KHQR payment testing
3. Enum validation fixes for Student and Payment models
4. "Wrong Hash" error resolved
5. Proper payment option configuration (abapay)
6. NGROK setup for webhook testing
7. PaywayCallbackService for URL handling
8. Understanding of Sakal's hosted checkout pattern

### 🔄 In Progress:
1. **Implementing Sakal's Hosted Checkout Pattern:**
   - Sakal uses a standalone page (`purchase_v2.blade.php`) that submits form to PayWay
   - PayWay's `checkout2-0.js` plugin handles the transformation to hosted UI
   - The form is submitted to PayWay's API, but the plugin intercepts and displays PayWay's hosted page
   - PayWay handles all payment UI including QR code generation and display

### 📋 Next Steps:
1. Update `payway-checkout.blade.php` to exactly match Sakal's pattern
2. Ensure form submission uses PayWay's hosted checkout flow
3. Test complete payment flow with PayWay's hosted UI
4. Implement webhook handling for payment completion
5. Document the final working implementation

---

## 8. Key Learnings

1. **Hash Generation is Critical:** The hash must match PayWay's exact format with all parameters in correct order
2. **API Key Confusion:** The "Public Key" in credentials is actually the API Key for HMAC
3. **Payment Options:** Use 'abapay' instead of 'khqr' if KHQR is not enabled for merchant
4. **Hosted Checkout Pattern:** PayWay provides a hosted checkout page using their checkout2-0.js plugin, not just a REST API
5. **Return URL Encoding:** Return URLs should be base64 encoded
6. **Reference Implementation:** Sakal's implementation is the correct pattern to follow

---

## 9. PayWay Integration Architecture

```
┌─────────────────┐
│  Test Page      │
│  (khqr.blade)   │
└────────┬────────┘
         │ 1. Create Payment
         ▼
┌─────────────────┐
│ TestKhqrCtrl    │
│ createPayment() │
└────────┬────────┘
         │ 2. Generate Form Data
         ▼
┌─────────────────┐
│ PaywayService   │
│ generateForm()  │
└────────┬────────┘
         │ 3. Return Form Data
         ▼
┌─────────────────┐
│  Test Page      │
│  Open Modal     │
└────────┬────────┘
         │ 4. Redirect to PayWay
         ▼
┌─────────────────┐
│ payway-checkout │
│ (Blade)         │
└────────┬────────┘
         │ 5. Submit Form to PayWay
         ▼
┌─────────────────┐
│ PayWay Hosted   │
│ Checkout Page   │
│ (with QR)       │
└────────┬────────┘
         │ 6. User Completes Payment
         ▼
┌─────────────────┐
│ Webhook         │
│ Callback        │
└────────┬────────┘
         │ 7. Update Payment Status
         ▼
┌─────────────────┐
│ Return Page     │
│ Show Success    │
└─────────────────┘
```

---

## 10. Reference: Sakal's Implementation

Sakal's `purchase_v2.blade.php` demonstrates the correct pattern:

1. **Standalone page** that receives form data from controller
2. **Form with hidden inputs** containing all PayWay parameters
3. **PayWay's checkout2-0.js plugin** included
4. **Iframe created dynamically** using jQuery
5. **Form auto-submitted** to PayWay on page load
6. **PayWay handles all UI** including QR code generation

This is the pattern we need to follow to achieve the user's requirement: "I want payway to handle on generate qr itself, not us"

---

## 11. Testing Checklist

- [ ] Test payment creation
- [ ] Test hash generation (should not show "Wrong Hash")
- [ ] Test PayWay hosted checkout page opens correctly
- [ ] Test QR code displays in PayWay's UI
- [ ] Test payment completion flow
- [ ] Test webhook callback
- [ ] Test payment status update
- [ ] Test return to success page

---

## 12. Important Notes

1. **Never modify the hash algorithm** - it must match Sakal's implementation exactly
2. **Use 'abapay' payment option** - 'khqr' is not enabled for this merchant
3. **Base64 encode return URLs** - both in hash and form parameters
4. **Include all parameters in hash** - even empty strings
5. **Use PayWay's hosted checkout** - don't try to render QR yourself
6. **PayWay's checkout2-0.js is required** - it handles the hosted UI transformation

---

*Document created: 2025-10-06*
*Last updated: 2025-10-06*
*Status: In Progress - Implementing Sakal's hosted checkout pattern*
