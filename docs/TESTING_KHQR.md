# Testing KHQR with the Test Page

I've created a beautiful test page for you to test the PayWay KHQR integration!

## 🚀 Quick Start

### 1. Start Your Server
```bash
php artisan serve
```

### 2. Open the Test Page
Visit: **http://localhost:8000/test/khqr**

---

## 📋 How to Use the Test Page

### **Step 1: Create Test Payment**
1. Enter amount (e.g., `100.00`)
2. Add description (e.g., `Test Registration Fee`)
3. Click **"Create Test Payment"**
4. You'll see a green success message with:
   - Payment UUID
   - Payment Code
   - Amount
   - Status

### **Step 2: Generate QR Code**
1. Copy the **Payment UUID** from Step 1
2. Paste it in the "Payment UUID" field in Step 2
3. Enter customer details (optional):
   - First Name (default: Sophea)
   - Last Name (default: Chan)
   - Phone (default: 012345678)
4. Click **"🔄 Generate QR Code"**
5. You'll see:
   - QR Code image
   - Payment information
   - Mobile deeplink button
   - Payment status

### **Step 3: Test Payment**

#### **Option A: Scan QR Code (Real Payment)**
1. Open ABA Mobile app
2. Scan the QR code
3. Complete payment
4. Status will update automatically!

#### **Option B: Monitor Status**
1. Click **"▶️ Start Checking Status"**
2. The page polls payment status every 3 seconds
3. When payment completes, you'll see an alert!

#### **Option C: Manual Status Check**
1. Use Step 3 to manually check status
2. Enter Payment UUID
3. Click "Check Payment Status"
4. See full payment details

---

## 🧪 Testing Scenarios

### **Scenario 1: Quick Test (Recommended)**
```
1. Create payment: $10.00
2. Generate QR
3. Check if QR appears
4. ✅ Success! (Don't need to pay)
```

### **Scenario 2: Full Payment Flow**
```
1. Create payment: $1.00
2. Generate QR
3. Scan with ABA app
4. Complete payment
5. Watch status change to "PAID"
6. ✅ Complete!
```

### **Scenario 3: Test Ngrok**
```
1. Start ngrok: ngrok http 8000
2. Update .env: NGROK_URL=https://abc123.ngrok-free.app
3. Create payment
4. Generate QR
5. Check logs for ngrok URL
6. ✅ Ngrok working!
```

---

## 🔍 What to Check

### ✅ **Success Indicators:**
1. QR code appears
2. Transaction UUID is shown
3. Expires time is displayed (15 min from now)
4. Mobile deeplink button works
5. Status polling works
6. Logs show ngrok URL (if using ngrok)

### ❌ **Potential Issues:**

**Issue 1: "Network Error"**
- Check if Laravel server is running
- Check browser console for errors

**Issue 2: "Failed to generate QR"**
- Check PayWay credentials in `.env`
- Check `storage/logs/laravel.log`
- Verify PayWay API URL is correct

**Issue 3: QR Code Not Showing**
- Check if PayWay returned `qr` field
- Look for errors in browser console
- Check network tab in DevTools

**Issue 4: Webhook Not Received**
- Make sure NGROK_URL is set (for local)
- Check ngrok dashboard: http://127.0.0.1:4040
- Verify webhook URL is publicly accessible

---

## 📊 Check Logs

### **Laravel Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Look for:**
```
PayWay Callback URL (ngrok) {"path":"/api/payway/webhook","url":"https://..."}
PayWay API Response {...}
PayWay Webhook Received {...}
```

### **Ngrok Dashboard:**
Visit: http://127.0.0.1:4040

**You'll see:**
- All incoming webhook requests
- Request/response details
- Timing information

---

## 🎨 Test Page Features

### **✨ What the Page Does:**

1. **Auto-creates test student** if none exists
2. **Generates unique payment codes** (PAY202510-0001)
3. **Shows QR code** in high resolution
4. **Auto-polls status** every 3 seconds
5. **Mobile deeplink** for ABA app
6. **Clean UI** with Tailwind CSS
7. **Real-time updates** with Alpine.js

### **📱 Mobile Testing:**

The page includes an **ABA Mobile App** deeplink button:
```
📱 Pay with ABA Mobile App
```

Click this on your phone to:
- Open ABA app directly
- Pre-fill payment details
- Complete payment faster

---

## 🛠️ Troubleshooting

### **Problem: Can't access /test/khqr**
**Solution:** Check routes are loaded
```bash
php artisan route:list | grep khqr
```

### **Problem: CSRF token mismatch**
**Solution:** Refresh page to get new token

### **Problem: Student not created**
**Solution:** Check database connection
```bash
php artisan migrate:status
```

### **Problem: Payment not saving**
**Solution:** Check logs and database
```bash
php artisan tinker
>>> App\Models\Payment::latest()->first()
```

---

## 🎯 Expected Results

### **After Creating Payment:**
```json
{
  "success": true,
  "payment": {
    "uuid": "9c8f1234-...",
    "payment_code": "PAY202510-0001",
    "amount": "100.00",
    "status": "pending",
    "description": "Test Payment"
  }
}
```

### **After Generating QR:**
```json
{
  "success": true,
  "data": {
    "transaction_uuid": "9c8f5678-...",
    "qr_url": "data:image/png;base64,...",
    "deeplink": "abapay://...",
    "expires_at": "2025-10-05 16:30:00",
    "payment_code": "PAY202510-0001"
  }
}
```

### **After Payment Completes:**
```json
{
  "success": true,
  "data": {
    "payment_uuid": "9c8f1234-...",
    "status": "paid",
    "paid_at": "2025-10-05 16:15:32",
    "transaction": {
      "status": "success",
      "qr_url": "...",
      "expires_at": "..."
    }
  }
}
```

---

## 🎉 Success Criteria

You know KHQR is working when:

✅ Test page loads without errors
✅ Payment creates successfully
✅ QR code displays properly
✅ Ngrok URL appears in logs (if using ngrok)
✅ Status polling works
✅ (Optional) Real payment completes

---

## 🚫 Remove Test Page in Production

**IMPORTANT:** This test page is for development only!

**Before deploying to production:**

1. Remove or protect the test routes:
```php
// routes/web.php
// Comment out or remove:
Route::group(['prefix' => 'test/khqr'], function () {
    // ...
});
```

2. Or add authentication:
```php
Route::group(['prefix' => 'test/khqr', 'middleware' => 'auth'], function () {
    // ...
});
```

---

## 💡 Tips

1. **Use small amounts** for testing ($1 - $10)
2. **Keep ngrok running** while testing webhooks
3. **Check logs** if something doesn't work
4. **Try manual status check** if auto-polling fails
5. **Clear browser cache** if UI acts weird

---

## 📞 Need Help?

Check these files if issues occur:
- `storage/logs/laravel.log` - Application logs
- Browser DevTools Console - JavaScript errors
- Browser DevTools Network - API requests
- http://127.0.0.1:4040 - Ngrok dashboard

---

Happy Testing! 🚀
