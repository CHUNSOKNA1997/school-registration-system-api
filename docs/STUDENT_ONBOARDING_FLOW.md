# Student Onboarding Flow (Application-First)

## Goal
Guest users can apply and pay first. Student dashboard access is only available after payment and account activation.

## Frontend Pages You Need
1. Application Form Page
2. Payment Page
3. Payment Pending / Processing State (can be inside Payment Page)
4. Onboarding Notice Page ("Check your personal email")
5. Account Activation Page (set password)
6. Student Login Page (school email as username)

## Redirect Flow
1. Submit application form successfully -> redirect to Payment Page.
2. Payment confirmed -> redirect to Onboarding Notice Page.
3. User clicks activation link from personal email -> open Activation Page.
4. Activation success -> redirect to Student Login Page (or auto-login, your choice).
5. Login success -> Student Dashboard.

## APIs (Current)
- `POST /api/v1/applications`
  - Creates application + provisional student/payment record.
- `POST /api/v1/applications/{application_uuid}/checkout-sessions`
  - Starts PayWay checkout session for that application.
- `GET /api/v1/applications/{application_uuid}/payment-status`
  - Poll this to check when payment becomes paid.
- `POST /api/v1/student-accounts/activate`
  - Completes account activation (set password using token from email).

## Recommended Frontend Sequence
1. Call `POST /api/v1/applications` from form submit.
2. Store `application_uuid` in app state.
3. Call `POST /api/v1/applications/{application_uuid}/checkout-sessions`.
4. Show hosted checkout/QR.
5. Poll `GET /api/v1/applications/{application_uuid}/payment-status` every 3-5 seconds.
6. Stop polling when `status` is paid and redirect to Onboarding Notice Page.

## Important Rules
- Do not require login before application submit.
- Do not grant student dashboard access before payment + activation.
- Use school email as login username only.
- Send onboarding/activation mail to personal email.

## Status Model (Conceptual)
- `payment_pending`
- `paid`
- `activated`

## Notes
- Existing authenticated student self-registration endpoint remains for backward compatibility, but the new frontend should use the application-first endpoints above.
