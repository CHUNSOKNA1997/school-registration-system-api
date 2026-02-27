# API Route Transition Plan (Non-Breaking)

## Scope
This document defines the naming transition introduced for API v1 while keeping existing clients working.

## Effective Date
February 27, 2026

## Sunset Date for Deprecated Routes
June 30, 2026 (23:59:59 UTC)

Deprecated endpoints now include:
- `Deprecation: true`
- `Sunset: <http-date>`
- `Link: <successor-path>; rel="successor-version"`

## Route Mapping

### Profile / Current User
- Old: `GET /api/v1/profile`
- New: `GET /api/v1/users/me`

- Old: `PUT /api/v1/profile`
- New: `PATCH /api/v1/users/me`

### Payment (KHQR / Status)
- Old: `POST /api/v1/payway/khqr/generate` with body `payment_uuid`
- New: `POST /api/v1/payments/{payment_uuid}/khqr`

- Old: `POST /api/v1/payway/payment/status` with body `payment_uuid`
- New: `GET /api/v1/payments/{payment_uuid}/status`

### User Activation
- Old: `POST /api/v1/users/{id}/activate`
- New: `PATCH /api/v1/users/{id}` with body:

```json
{
  "is_active": true
}
```

## Backward Compatibility
- Old routes remain active during migration window.
- New canonical routes are available immediately.
