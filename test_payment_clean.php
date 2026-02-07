<?php
/**
 * Direct API test - simulates real browser call
 */
// Start with clean buffer
ob_start();

// Set params as if from Ajax
$_GET['action'] = 'initiate_payment';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Start session BEFORE any output
session_start();
$_SESSION['user_id'] = 24;
$_SESSION['role'] = 'patient';

// Prepare JSON POST data
$_POST = [];
$rawInput = json_encode([
    'transaction_type' => 'consultation_fee',
    'related_id' => 18,
    'amount' => 200,
    'payment_method' => 'card'
]);

// Mock php://input
file_put_contents('php://input', $rawInput);

// Include the API
include 'payment_api.php';

// Get output
$output = ob_get_clean();

// Check for JSON validity
echo "=== CLEAN API OUTPUT TEST ===\n";
echo "Output length: " . strlen($output) . " bytes\n\n";

// Check for any text before  {
$jsonStart = strpos($output, '{');
if ($jsonStart > 0) {
    echo "❌ FOUND OUTPUT BEFORE JSON ($jsonStart bytes):\n";
    echo "Hex: " . bin2hex(substr($output, 0, $jsonStart)) . "\n";
    echo "Text: " . substr($output, 0, $jsonStart) . "\n\n";
} else if ($jsonStart === 0) {
    echo "✅ JSON starts immediately (no garbage)\n\n";
}

echo "Full output:\n";
echo $output . "\n\n";

// Try to parse
$decoded = json_decode($output);
if ($decoded === null) {
    echo "❌ JSON PARSE FAILED: " . json_last_error_msg() . "\n";
} else {
    echo "✅ VALID JSON\n";
    print_r($decoded);
}