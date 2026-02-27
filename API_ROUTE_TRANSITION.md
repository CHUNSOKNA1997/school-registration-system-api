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

### Auth / Sessions
- Old: `POST /api/v1/auth/register`
- New (canonical): `POST /api/v1/registrations`

- Old: `POST /api/v1/auth/login`
- New (canonical): `POST /api/v1/sessions`

- Old: `DELETE /api/v1/auth/logout`
- New (canonical): `DELETE /api/v1/sessions/current`

- Old: `GET /api/v1/auth/user`
- New (canonical): `GET /api/v1/sessions/current`

### Profile / Current User
- Old: `GET /api/v1/profile`
- New: `GET /api/v1/users/me`

- Old: `PUT /api/v1/profile`
- New: `PATCH /api/v1/users/me`

### Payment (KHQR / Status)
- Old: `POST /api/v1/payway/khqr/generate` with body `payment_uuid`
- New (canonical): `POST /api/v1/payments/{payment_uuid}/checkout-sessions`
- Deprecated alias: `POST /api/v1/payments/{payment_uuid}/khqr`

- Old: `POST /api/v1/payway/payment/status` with body `payment_uuid`
- New (canonical): `GET /api/v1/payments/{payment_uuid}`
- Deprecated alias: `GET /api/v1/payments/{payment_uuid}/status`

### PayWay Webhook
- New (canonical): `POST /api/v1/webhooks/payway`
- Deprecated alias: `POST /api/v1/payway/webhook`
- Legacy alias: `POST /api/payway/webhook`

### User Activation
- Old: `POST /api/v1/users/{id}/activate`
- New: `PATCH /api/v1/users/{id}` with body:

```json
{
  "is_active": true
}
```

### Student Update
- New (canonical): `PATCH /api/v1/students/{student}`
- Deprecated alias: `PUT /api/v1/students/{student}`

### Student Enrollments
- New (canonical): `PATCH /api/v1/students/{student}/enrollments/{enrollment}`
- Deprecated alias: `PUT /api/v1/students/{student}/enrollments/{enrollment}`

### Enrollment Batches
- Old: `POST /api/v1/enrollments/bulk`
- New (canonical): `POST /api/v1/enrollment-batches`

## Backward Compatibility
- Old routes remain active during migration window.
- New canonical routes are available immediately.
