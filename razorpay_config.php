<?php
/**
 * Razorpay Configuration
 * Centralized configuration for Razorpay payment gateway
 */

// Razorpay API Credentials
// NOTE: For production, move these to environment variables or secure config
define('RAZORPAY_KEY_ID', 'rzp_test_S9MbhUjEmgamjY');
define('RAZORPAY_KEY_SECRET', 'hW38hNTE3X7uAuInfsHFqn9f');

// Webhook Secret (generated in Razorpay Dashboard)
// This will be used to verify webhook signatures
define('RAZORPAY_WEBHOOK_SECRET', 'your_webhook_secret_here');

// Razorpay API Base URL
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');

// Currency
define('RAZORPAY_CURRENCY', 'INR');

// Payment timeout in seconds
define('RAZORPAY_TIMEOUT', 300);

// TEST MODE - Set to true to bypass Razorpay and simulate payments
// Set to false to use Razorpay's actual test mode with test credentials
define('PAYMENT_TEST_MODE', false);

/**
 * Get Razorpay API authentication header
 */
function getRazorpayAuthHeader() {
    return 'Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
}

/**
 * Verify Razorpay payment signature
 * 
 * @param string $orderId Razorpay order ID
 * @param string $paymentId Razorpay payment ID
 * @param string $signature Razorpay signature
 * @return bool True if signature is valid
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Verify Razorpay webhook signature
 * 
 * @param string $payload Webhook payload
 * @param string $signature Webhook signature from header
 * @return bool True if signature is valid
 */
function verifyWebhookSignature($payload, $signature) {
    $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_WEBHOOK_SECRET);
    return hash_equals($expectedSignature, $signature);
}

/**
 * Convert amount to paise (smallest currency unit)
 * 
 * @param float $amount Amount in rupees
 * @return int Amount in paise
 */
function convertToPaise($amount) {
    return (int) round($amount * 100);
}

/**
 * Convert amount from paise to rupees
 * 
 * @param int $paise Amount in paise
 * @return float Amount in rupees
 */
function convertToRupees($paise) {
    return $paise / 100;
}