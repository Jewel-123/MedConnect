<?php
require_once 'razorpay_config.php';

echo "Testing Razorpay Credentials\n";
echo "============================\n\n";

echo "Key ID: " . RAZORPAY_KEY_ID . "\n";
echo "Key Secret: " . substr(RAZORPAY_KEY_SECRET, 0, 5) . "..." . "\n\n";

// Test API call
$orderData = [
    'amount' => 50000, // 500 INR in paise
    'currency' => 'INR',
    'receipt' => 'test_' . time()
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
]);

echo "Making test API call to Razorpay...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    echo "✓ SUCCESS! Razorpay credentials are valid.\n";
} else {
    echo "✗ FAILED! Razorpay authentication failed.\n";
    $error = json_decode($response, true);
    if (isset($error['error'])) {
        echo "Error: " . $error['error']['description'] . "\n";
    }
}