# Razorpay Integration Setup Guide

## Get Your Razorpay Credentials

### Step 1: Sign Up / Login to Razorpay
1. Go to https://dashboard.razorpay.com/signup
2. Create an account or login if you already have one

### Step 2: Get Test API Keys
1. After login, go to **Settings** → **API Keys**
2. Click on **Generate Test Key** (if not already generated)
3. You will see:
   - **Key ID**: Starts with `rzp_test_`
   - **Key Secret**: Click "Show" to reveal it

### Step 3: Update Credentials in MedConnect

Open `razorpay_config.php` and update lines 9-10:

```php
define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_KEY_ID_HERE');
define('RAZORPAY_KEY_SECRET', 'YOUR_KEY_SECRET_HERE');
```

**Example:**
```php
define('RAZORPAY_KEY_ID', 'rzp_test_AbCdEfGhIjKlMnOp');
define('RAZORPAY_KEY_SECRET', 'QrStUvWxYz1234567890');
```

---

## Test the Integration

### Test Cards (Razorpay Test Mode)

**Success:**
- Card Number: `4111 1111 1111 1111`
- CVV: Any 3 digits (e.g., `123`)
- Expiry: Any future date (e.g., `12/25`)

**Failure:**
- Card Number: `4000 0000 0000 0002`

### Test Flow

1. **Book an Appointment**
   - Go to `appointment_booking.php`
   - Select a doctor
   - Choose date and time
   - Click "Confirm Appointment"

2. **Complete Payment**
   - You'll be redirected to payment gateway
   - Click "Pay Now"
   - Razorpay checkout will open
   - Use test card: `4111 1111 1111 1111`
   - Complete payment

3. **Verify Success**
   - Payment should complete
   - Appointment status updated to 'paid'
   - Doctor receives notification
   - Doctor can see appointment in dashboard

---

## Production Setup

### Step 1: Activate Your Account
1. Complete KYC verification in Razorpay dashboard
2. Add bank account details
3. Wait for approval (usually 24-48 hours)

### Step 2: Get Live API Keys
1. Go to **Settings** → **API Keys**
2. Click on **Generate Live Key**
3. Save the Key ID and Key Secret

### Step 3: Update to Live Credentials
In `razorpay_config.php`:

```php
define('RAZORPAY_KEY_ID', 'rzp_live_YOUR_LIVE_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_LIVE_KEY_SECRET');
```

### Step 4: Setup Webhook
1. Go to **Settings** → **Webhooks**
2. Add webhook URL: `https://yourdomain.com/razorpay_webhook.php`
3. Select events: `payment.captured`, `payment.failed`, `order.paid`
4. Copy the webhook secret
5. Update in `razorpay_config.php`:
   ```php
   define('RAZORPAY_WEBHOOK_SECRET', 'your_webhook_secret_here');
   ```

---

## Important Notes

⚠️ **Security:**
- Never commit API keys to version control
- Use environment variables in production
- Keep webhook secret secure

⚠️ **Testing:**
- Always test in test mode first
- Use test cards only in test mode
- Real cards won't work in test mode

⚠️ **Production:**
- Ensure SSL certificate is installed
- Test webhook integration
- Monitor payment success rate

---

## Current Status

✅ Razorpay integration is **fully implemented**
✅ Order creation via Razorpay API
✅ Payment signature verification
✅ Webhook handler ready
✅ Appointment payment flow complete

❌ **Action Required:** Update Razorpay credentials in `razorpay_config.php`

---

## Support

- **Razorpay Docs**: https://razorpay.com/docs/
- **Test Cards**: https://razorpay.com/docs/payments/payments/test-card-details/
- **Support**: https://razorpay.com/support/
