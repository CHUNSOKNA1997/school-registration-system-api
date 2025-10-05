# Using NGROK with PayWay for Local Development

## Why Do You Need NGROK? 🤔

When developing locally, your app runs on `http://localhost:8000`, which is only accessible from your computer. **PayWay cannot send webhooks to localhost** because it's not a public URL.

**NGROK solves this** by creating a secure tunnel from a public URL to your localhost.

```
PayWay Webhook → https://abc123.ngrok.io → Your localhost:8000
```

---

## Setup Steps

### 1. Install NGROK

**Download:**
- https://ngrok.com/download

**Or install via package manager:**

```bash
# macOS
brew install ngrok

# Linux (snap)
sudo snap install ngrok

# Windows
# Download from https://ngrok.com/download
```

### 2. Sign Up & Get Auth Token

1. Create free account: https://dashboard.ngrok.com/signup
2. Get your auth token: https://dashboard.ngrok.com/get-started/your-authtoken
3. Configure ngrok:

```bash
ngrok config add-authtoken YOUR_AUTH_TOKEN
```

### 3. Start Laravel Server

```bash
php artisan serve
# Serving on http://127.0.0.1:8000
```

### 4. Start NGROK Tunnel

**In a new terminal:**

```bash
ngrok http 8000
```

**You'll see output like:**

```
ngrok

Session Status                online
Account                       Your Name (Plan: Free)
Version                       3.x.x
Region                        United States (us)
Latency                       -
Web Interface                 http://127.0.0.1:4040
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:8000

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```

**Copy the HTTPS URL:** `https://abc123.ngrok-free.app`

### 5. Update .env File

```env
# NGROK (for local development)
NGROK_URL=https://abc123.ngrok-free.app
```

### 6. Test It Works

**Visit your ngrok URL in browser:**
```
https://abc123.ngrok-free.app/api/user/v1/auth/user
```

You should see your Laravel app!

---

## How It Works in Code

### Automatic Detection

The `PaywayCallbackService` automatically handles different environments:

```php
// config/services.php
'ngrok' => [
    'url' => env('NGROK_URL', null),
],
```

**In Development:**
```php
PaywayCallbackService::getCallbackUrl('/api/payway/webhook');
// Returns: https://abc123.ngrok-free.app/api/payway/webhook (base64 encoded)
```

**In Production:**
```php
PaywayCallbackService::getCallbackUrl('/api/payway/webhook');
// Returns: https://yoursite.com/api/payway/webhook (base64 encoded)
```

### Flow Diagram

```
┌──────────────────────────────────────────────────────────────┐
│  Student pays via ABA app                                    │
└──────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────┐
│  PayWay processes payment                                    │
└──────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────┐
│  PayWay sends webhook to:                                    │
│  https://abc123.ngrok-free.app/api/payway/webhook           │
└──────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────┐
│  NGROK tunnels to:                                           │
│  http://localhost:8000/api/payway/webhook                   │
└──────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌──────────────────────────────────────────────────────────────┐
│  Your Laravel app receives webhook                           │
│  Payment status updates to "paid"                            │
└──────────────────────────────────────────────────────────────┘
```

---

## Testing Webhook

### 1. Generate KHQR

```bash
POST https://abc123.ngrok-free.app/api/payway/khqr/generate
Authorization: Bearer {token}
{
    "payment_uuid": "uuid-here"
}
```

### 2. Check Logs

PayWay will send callbacks to your ngrok URL. Check logs:

```bash
tail -f storage/logs/laravel.log
```

You should see:
```
[2025-10-05 16:00:00] local.INFO: PayWay Callback URL (ngrok) {"path":"/api/payway/webhook","url":"https://abc123.ngrok-free.app/api/payway/webhook"}
```

### 3. Monitor Requests

NGROK provides a web interface to see all requests:

```
http://127.0.0.1:4040
```

You can see:
- All incoming webhook requests
- Request headers
- Request body
- Response status

---

## Common Issues

### Issue 1: NGROK URL Changes Every Restart

**Problem:** Free ngrok gives you a new URL each time you restart.

**Solution:**
- **Option A:** Get ngrok Pro (static domains)
- **Option B:** Update `.env` each time you restart ngrok
- **Option C:** Use ngrok config with reserved domain (paid)

### Issue 2: Webhook Not Received

**Check:**
1. ✅ NGROK is running
2. ✅ `.env` has correct NGROK_URL
3. ✅ Laravel server is running
4. ✅ Check ngrok web interface (http://127.0.0.1:4040)

### Issue 3: "Invalid Host" Error

**Solution:**
Add to `.env`:
```env
APP_URL=https://abc123.ngrok-free.app
```

Or disable host verification:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustHosts(at: ['abc123.ngrok-free.app']);
})
```

---

## Production Deployment

**Important:** NGROK is for **development only**!

In production:
1. Remove `NGROK_URL` from `.env`
2. Set `APP_ENV=production`
3. Use your real domain: `https://school.example.com`

The `PaywayCallbackService` will automatically use the production URL.

---

## Alternative: LocalTunnel

If you don't want to use ngrok, try LocalTunnel:

```bash
npm install -g localtunnel
lt --port 8000
```

Then update `.env`:
```env
NGROK_URL=https://your-subdomain.loca.lt
```

---

## Quick Reference

| Environment | Webhook URL | Source |
|-------------|-------------|--------|
| **Local (with ngrok)** | `https://abc123.ngrok-free.app/api/payway/webhook` | `.env` NGROK_URL |
| **Local (without ngrok)** | `http://localhost:8000/api/payway/webhook` ⚠️ Won't work! | Fallback |
| **Production** | `https://yoursite.com/api/payway/webhook` | APP_URL |

---

## Summary

1. ✅ Install ngrok
2. ✅ Start Laravel: `php artisan serve`
3. ✅ Start ngrok: `ngrok http 8000`
4. ✅ Copy ngrok URL
5. ✅ Update `.env`: `NGROK_URL=https://...`
6. ✅ Test payment flow
7. ✅ Check logs and ngrok dashboard

**Done!** PayWay can now send webhooks to your local development environment! 🎉
