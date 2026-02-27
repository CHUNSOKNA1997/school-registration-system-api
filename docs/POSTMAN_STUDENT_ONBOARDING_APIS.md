# Postman Samples: Student Onboarding APIs

## Base
- Base URL: `{{base_url}}` (example: `http://127.0.0.1:8000`)
- Prefix: `/api/v1`
- Default header: `Content-Type: application/json`

## Suggested Postman Environment Variables
- `base_url`
- `application_uuid`
- `payment_uuid`
- `school_email`
- `activation_token`
- `student_password`
- `student_token`

## 1) Get Payment Plans
### Request
- Method: `GET`
- URL: `{{base_url}}/api/v1/payment-plans`
- Body: none

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "default_plan": "monthly",
    "currency": "USD",
    "monthly_tuition": "500.00",
    "plans": [
      {
        "code": "monthly",
        "label": "Monthly",
        "months": 1,
        "payment_period": "monthly",
        "discount_rate": 0,
        "amount": "500.00",
        "discount_amount": "0.00",
        "payable_amount": "500.00"
      },
      {
        "code": "half_year",
        "label": "Half Year (6 months upfront)",
        "months": 6,
        "payment_period": "monthly",
        "discount_rate": 0,
        "amount": "3000.00",
        "discount_amount": "0.00",
        "payable_amount": "3000.00"
      },
      {
        "code": "yearly",
        "label": "Yearly (12 months upfront)",
        "months": 12,
        "payment_period": "yearly",
        "discount_rate": 0.1,
        "amount": "6000.00",
        "discount_amount": "600.00",
        "payable_amount": "5400.00"
      }
    ]
  }
}
```

## 2) Submit Application
### Request
- Method: `POST`
- URL: `{{base_url}}/api/v1/applications`
- Body:
```json
{
  "first_name": "Sokna",
  "last_name": "Chun",
  "khmer_name": null,
  "date_of_birth": "2014-02-27",
  "place_of_birth": "Phnom Penh",
  "gender": "female",
  "student_type": "regular",
  "nationality": "Cambodian",
  "phone": "012111222",
  "email": "applicant@example.com",
  "current_address": "Phnom Penh",
  "permanent_address": "Kandal",
  "parent_name": "Parent Name",
  "parent_phone": "099111222",
  "parent_occupation": "Teacher",
  "emergency_contact": "011222333",
  "emergency_contact_relationship": "Aunt",
  "class_id": null,
  "shift": "morning",
  "registration_date": "2026-02-27",
  "academic_year": "2025-2026",
  "payment_plan": "monthly",
  "previous_school": "ABC School",
  "photo": null,
  "documents": [],
  "notes": null
}
```

### Success Response (201)
```json
{
  "success": true,
  "data": {
    "application_uuid": "ae7f93ca-0213-4f3c-8e2d-05ce3ea111de",
    "status": "payment_pending",
    "payment_plan": "monthly",
    "payment_uuid": "6e5fb1f4-86d3-4a3d-906a-e4452f8980db",
    "payment_code": "PAY-20260227-00124",
    "payment_status": "pending",
    "amount": "500.00",
    "school_email": null,
    "onboarding_sent_at": null
  },
  "message": "Application submitted successfully"
}
```

### Error Response (422 duplicate identity)
```json
{
  "success": false,
  "message": "Student is already registered with the same name, date of birth, and parent phone"
}
```

### Error Response (422 validation)
```json
{
  "message": "The first name field is required.",
  "errors": {
    "first_name": [
      "The first name field is required."
    ]
  }
}
```

## 3) Create Checkout Session
### Request
- Method: `POST`
- URL: `{{base_url}}/api/v1/applications/{{application_uuid}}/checkout-sessions`
- Body (optional override fields):
```json
{
  "first_name": "Sokna",
  "last_name": "Chun",
  "email": "applicant@example.com",
  "phone": "012111222"
}
```

### Success Response (200, hosted checkout mode)
```json
{
  "success": true,
  "data": {
    "application_uuid": "ae7f93ca-0213-4f3c-8e2d-05ce3ea111de",
    "status": "payment_pending",
    "payment_uuid": "6e5fb1f4-86d3-4a3d-906a-e4452f8980db",
    "checkout": {
      "success": true,
      "mode": "hosted_checkout",
      "transaction_uuid": "dc244958-0d80-43ad-a1d8-eacbb92ea12c",
      "tran_id": "PAY2026-022711313536",
      "amount": "500.00",
      "qr_string": null,
      "qr_url": null,
      "deeplink": null,
      "qr_currency_code": null,
      "checkout_qr_url": null,
      "expires_at": "2026-02-27T04:46:35.000000Z",
      "payment_code": "PAY-20260227-00124",
      "hosted_checkout_url": "/payway/checkout/ae7f93ca-0213-4f3c-8e2d-05ce3ea111de"
    }
  }
}
```

### Error Response (404)
```json
{
  "success": false,
  "message": "Application not found"
}
```

### Error Response (500 PayWay failure)
```json
{
  "success": false,
  "message": "Failed to generate KHQR: PayWay API request failed (HTTP 403): {\"status\":{\"code\":\"1\",\"message\":\"Wrong Hash.\"}}"
}
```

## 4) Check Payment Status
### Request
- Method: `GET`
- URL: `{{base_url}}/api/v1/applications/{{application_uuid}}/payment-status`
- Body: none

### Success Response (200, pending)
```json
{
  "success": true,
  "data": {
    "application_uuid": "ae7f93ca-0213-4f3c-8e2d-05ce3ea111de",
    "application_status": "payment_pending",
    "payment_status": "pending",
    "payment_uuid": "6e5fb1f4-86d3-4a3d-906a-e4452f8980db",
    "payment_code": "PAY-20260227-00124",
    "paid_at": null,
    "school_email": null,
    "onboarding_sent_at": null,
    "can_activate": false
  }
}
```

### Success Response (200, paid and account provisioned)
```json
{
  "success": true,
  "data": {
    "application_uuid": "ae7f93ca-0213-4f3c-8e2d-05ce3ea111de",
    "application_status": "account_created",
    "payment_status": "paid",
    "payment_uuid": "6e5fb1f4-86d3-4a3d-906a-e4452f8980db",
    "payment_code": "PAY-20260227-00124",
    "paid_at": "2026-02-27T06:24:10.000000Z",
    "school_email": "sokna.chun@starlight.edu.kh",
    "onboarding_sent_at": "2026-02-27T06:24:11.000000Z",
    "can_activate": true
  }
}
```

## 5) Activate Student Account
### Request
- Method: `POST`
- URL: `{{base_url}}/api/v1/student-accounts/activate`
- Body:
```json
{
  "token": "{{activation_token}}",
  "password": "new-password-123",
  "password_confirmation": "new-password-123"
}
```

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "message": "Student account activated successfully",
    "school_email": "sokna.chun@starlight.edu.kh",
    "token": "1|yU4...sanctum-token",
    "user": {
      "id": 25,
      "uuid": "6d1e1d1f-77ac-4405-aa8f-27f044b0fd80",
      "name": "Sokna Chun",
      "email": "sokna.chun@starlight.edu.kh",
      "phone": "012111222",
      "is_admin": false,
      "account_type": "student",
      "is_active": true,
      "last_login_at": null,
      "created_at": "2026-02-27T06:24:10.000000Z",
      "updated_at": "2026-02-27T06:26:03.000000Z"
    }
  }
}
```

### Error Response (422 invalid/expired token)
```json
{
  "success": false,
  "message": "Invalid or expired activation token"
}
```

## 6) Login (School Email)
### Request
- Method: `POST`
- URL: `{{base_url}}/api/v1/sessions`
- Body:
```json
{
  "email": "{{school_email}}",
  "password": "{{student_password}}"
}
```

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 25,
      "uuid": "6d1e1d1f-77ac-4405-aa8f-27f044b0fd80",
      "name": "Sokna Chun",
      "email": "sokna.chun@starlight.edu.kh",
      "phone": "012111222",
      "is_admin": false,
      "account_type": "student",
      "is_active": true,
      "last_login_at": "2026-02-27 13:20:01",
      "created_at": "2026-02-27 13:10:22",
      "updated_at": "2026-02-27 13:20:01"
    },
    "token": "2|R2J...sanctum-token"
  },
  "message": "Login successful"
}
```

### Error Response (422 wrong credentials)
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

## 7) Get Current Session User (Optional)
### Request
- Method: `GET`
- URL: `{{base_url}}/api/v1/sessions/current`
- Header: `Authorization: Bearer {{student_token}}`

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "id": 25,
    "uuid": "6d1e1d1f-77ac-4405-aa8f-27f044b0fd80",
    "name": "Sokna Chun",
    "email": "sokna.chun@starlight.edu.kh",
    "phone": "012111222",
    "is_admin": false,
    "account_type": "student",
    "is_active": true,
    "last_login_at": "2026-02-27 13:20:01",
    "created_at": "2026-02-27 13:10:22",
    "updated_at": "2026-02-27 13:20:01"
  }
}
```

## 8) Logout Current Session (Optional)
### Request
- Method: `DELETE`
- URL: `{{base_url}}/api/v1/sessions/current`
- Header: `Authorization: Bearer {{student_token}}`

### Success Response (200)
```json
{
  "success": true,
  "data": [],
  "message": "Logged out successfully"
}
```

## Useful Postman Test Snippets
### Save `application_uuid` after submit
```javascript
const body = pm.response.json();
if (body?.data?.application_uuid) {
  pm.environment.set("application_uuid", body.data.application_uuid);
}
if (body?.data?.payment_uuid) {
  pm.environment.set("payment_uuid", body.data.payment_uuid);
}
```

### Save `school_email` and `token` after activate/login
```javascript
const body = pm.response.json();
if (body?.data?.school_email) {
  pm.environment.set("school_email", body.data.school_email);
}
if (body?.data?.token) {
  pm.environment.set("student_token", body.data.token);
}
```
