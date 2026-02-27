# Student Application Redirect Flow

## Immediate Answer
After user clicks `Submit` on application form, redirect to the **Payment Page**.

## End-to-End Frontend Flow
1. User submits application form.
2. Frontend calls `POST /api/v1/applications`.
3. Backend returns `application_uuid`.
4. Frontend calls `POST /api/v1/applications/{application_uuid}/checkout-sessions`.
5. Redirect (or render) **Payment Page** with PayWay checkout/QR.
6. Frontend polls `GET /api/v1/applications/{application_uuid}/payment-status`.
7. When payment is confirmed, redirect to **Onboarding Notice Page**:
   - Message: "Registration and payment successful."
   - Message: "Please check your personal email for account activation."
8. User receives onboarding email at personal email with:
   - School login email (username), e.g. `sokna.chun@starlight.edu.kh`
   - One-time activation link
9. User opens activation link, sets password.
10. User logs in using school email + password to student dashboard.

## Redirect Rules
- `Submit` success -> **Payment Page**
- Payment success -> **Onboarding Notice Page**
- Never redirect to dashboard directly after form submit or payment.

## Why
- Student can apply without login.
- Payment is required before account activation.
- School email is used as login identity.
- Personal email is only used for onboarding delivery.
