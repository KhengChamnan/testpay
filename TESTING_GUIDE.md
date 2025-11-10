# ABA PayWay Payment Integration - Testing Guide

## ✅ Implementation Complete!

### Backend (Laravel) - DONE ✅
- ✅ Payments table migration created
- ✅ Payment model with helper methods
- ✅ ABA PayWay service for API integration
- ✅ Payment controller with all endpoints
- ✅ API routes configured
- ✅ Environment variables configured

### Frontend (Flutter) - DONE ✅
- ✅ Payment models
- ✅ Payment service
- ✅ Payment screen (form)
- ✅ Payment processing screen (QR + polling)
- ✅ Payment success screen

---

## 🚀 How to Test

### 1. Start Backend (Laravel)
```bash
cd backend
php artisan serve
```
Backend runs on: http://localhost:8000

### 2. Start ngrok (for ABA callbacks)
```bash
ngrok http 8000
```
Update .env with your ngrok URL:
```
ABA_RETURN_URL=https://YOUR-NGROK-URL.ngrok-free.app/api/payway/return
ABA_CANCEL_URL=https://YOUR-NGROK-URL.ngrok-free.app/api/payway/cancel
ABA_CONTINUE_SUCCESS_URL=https://YOUR-NGROK-URL.ngrok-free.app/api/payway/success
```

### 3. Run Flutter App
```bash
cd frontend
flutter run
```

### 4. Test Payment Flow
1. Open app and fill payment details
2. Click "Pay Now"
3. QR code will be displayed
4. Open ABA mobile app and scan QR
5. Complete payment in ABA app
6. App will auto-detect payment and show success screen

---

## 📋 API Endpoints

### Create Payment
**POST** `/api/payment/create`
```json
{
  "amount": 5.00,
  "currency": "USD",
  "payment_option": "abapay_khqr_deeplink",
  "firstname": "John",
  "lastname": "Doe",
  "email": "test@example.com",
  "phone": "012345678"
}
```

### Check Payment Status
**GET** `/api/payment/status?tran_id=TXN123456`

### Payment Callback (ABA calls this)
**POST** `/api/payway/return`

---

## 🔍 Monitoring

### Check Laravel logs:
```bash
tail -f backend/storage/logs/laravel.log
```

### Check payment in database:
```bash
cd backend
php artisan tinker
>>> App\Models\Payment::all();
```

---

## ⚠️ Important Notes

1. **ngrok URL**: Must be accessible from internet for ABA callbacks
2. **Polling**: Flutter app polls every 3 seconds for payment status
3. **Test Mode**: Using ABA sandbox credentials
4. **QR Lifetime**: QR code expires in 10 minutes

---

## 📱 Flutter App Features

- Form validation
- Loading states
- QR code display
- Deeplink support (opens ABA app)
- Auto-polling payment status
- Success/error handling
- Clean UI with Material Design

---

## 🛠️ Troubleshooting

**Payment not updating?**
- Check backend logs for callback
- Verify ngrok is running
- Check ABA_RETURN_URL in .env

**QR not showing?**
- Check API response in logs
- Verify ABA credentials
- Check network connection

**App crashes?**
- Run `flutter pub get`
- Check for compilation errors
- Restart app

---

## 📦 Dependencies

### Laravel
- Laravel 11.x
- Guzzle HTTP client

### Flutter
- http: ^1.2.0
- url_launcher: ^6.2.4
- qr_flutter: ^4.1.0

---

## 🎯 Testing Checklist

- [ ] Backend server running
- [ ] ngrok tunnel active
- [ ] Flutter app launched
- [ ] Create payment request
- [ ] QR code displayed
- [ ] Scan with ABA app
- [ ] Complete payment
- [ ] Callback received
- [ ] Status updated to "paid"
- [ ] Success screen shown

---

**Need help?** Check logs in `backend/storage/logs/laravel.log`
