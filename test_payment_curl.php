<?php
// Direct minimal test of payment API to isolate issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing payment_api.php response...\n\n";

// Simulate request
$url = 'http://localhost/medconnect/payment_api.php?action=initiate_payment';
$data = json_encode([
    'transaction_type' => 'consultation_fee',
    'related_id' => 18,
    'amount' => 200,
    'payment_method' => 'card'
]);

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);

// Add cookie for session (you may need to adjust this)
curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_COOKIEJAR, '');

// Execute
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: " . ($error ?: 'None') . "\n";
echo "Response Length: " . strlen($response) . " bytes\n\n";

echo "=== RAW RESPONSE ===\n";
echo $response . "\n";
echo "\n=== END RAW RESPONSE ===\n\n";

// Check for content before JSON
$jsonStart = strpos($response, '{');
if ($jsonStart > 0) {
    echo "❌ FOUND CONTENT BEFORE JSON ($jsonStart bytes):\n";
    $garbage = substr($response, 0, $jsonStart);
    echo "Text: " . $garbage . "\n";
    echo "Hex: " . bin2hex($garbage) . "\n\n";
}

// Try to parse
$decoded = json_decode($response);
if ($decoded === null && strlen($response) > 0) {
    echo "❌ JSON PARSE ERROR: " . json_last_error_msg() . "\n";
} else if ($decoded) {
    echo "✅ VALID JSON\n";
    print_r($decoded);
}