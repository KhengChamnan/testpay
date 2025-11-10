# ✅ Payment Integration Plan: ABA PayWay (Flutter + Laravel)

This document describes the required tasks to implement ABA PayWay online payment using QR / Deep Link in a Flutter mobile application with a Laravel backend.

---

## 1. Backend (Laravel)

### 1.1 Create Database Fields
Add a `payments` table or update existing booking table:

- `tran_id` (string, unique)
- `payment_status` (default: `pending`)
- `amount`
- `user_id` or `booking_id`

Example status values:
```
pending
paid
failed
expired
```

### 1.2 Create Payment Request Endpoint
**POST /api/payment/create**

Responsibilities:
1. Generate unique `tran_id`
2. Send Purchase request to ABA API
3. Save payment record in DB with `payment_status = "pending"`
4. Return the QR string or deep link to the Flutter app

### 1.3 Create Return Callback Endpoint (Very Important)
**POST /api/payway/return**

This endpoint will be called by ABA **after payment is completed**.

Tasks:
1. Receive callback data (`tran_id`, `status`, etc.)
2. Log full callback payload
3. Update payment record in DB to `payment_status = "paid"` if successful

> **This route must be PUBLIC and accessible from the internet.**
> If developing locally, use NGROK:
>
> `ngrok http 8000`

### 1.4 Create Payment Status Check Endpoint
**GET /api/payment/status?tran_id=...**

Return:
```json
{
  "payment_status": "pending" | "paid" | "failed"
}
```

Flutter will use this to know when to show the success screen.

---

## 2. Mobile App (Flutter)

### 2.1 Initiate Payment
When the user taps "Pay":
1. Request `/api/payment/create`
2. Receive:
   - `qr_string` (if QR display)
   - or `abapay_deeplink` (to open ABA app)

### 2.2 Display Payment UI
- If using QR: show QR image
- If using deep link: open using `url_launcher`

### 2.3 Start Polling for Payment Result
Use `Timer.periodic` every 2–3 seconds:
```
GET /api/payment/status?tran_id=...
```

### 2.4 Stop Polling and Show Success
When backend returns:
```json
{ "payment_status": "paid" }
```
Navigate to success page.

---

## 3. Payment Flow Summary

```
Flutter → /payment/create → Backend → ABA API → QR/DeepLink → User Pays
                        ↓
               ABA → /payway/return → Backend updates DB
                        ↓
             Flutter polls /payment/status until "paid"
                        ↓
                 Flutter shows success UI
```

---

## 4. Testing Checklist

| Step | Expected Result |
|------|----------------|
| Generate QR | Works |
| Scan and pay in ABA app | Works |
| Callback hits `/payway/return` | Logged in backend |
| DB payment_status becomes `paid` | Yes |

Here is my url:  
elegant-many-oyster.ngrok-free.app