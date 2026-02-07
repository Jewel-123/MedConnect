<?php
// Test payment API directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== PAYMENT API DEBUG TEST ===\n\n";

// Simulate a payment request
$_POST['action'] = 'initiate_payment';
$_POST['amount'] = 500;
$_POST['transaction_type'] = 'consultation_fee';
$_POST['related_id'] = 1;

// Start session
session_start();
$_SESSION['user_id'] = 24; // Test patient
$_SESSION['role'] = 'patient';

echo "Request parameters set\n";
echo "Including payment_api.php...\n\n";

// Capture output
ob_start();
try {
    include 'payment_api.php';
    $output = ob_get_clean();
    
    echo "=== RAW OUTPUT ===\n";
    echo $output;
    echo "\n\n=== END OUTPUT ===\n";
    
    // Try to parse as JSON
    echo "\n=== JSON VALIDATION ===\n";
    $decoded = json_decode($output);
    if ($decoded === null) {
        echo "❌ INVALID JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        echo "Output length: " . strlen($output) . " bytes\n";
        
        // Show hex dump of last 100 chars
        echo "\nLast 100 characters (visible):\n";
        echo substr($output, -100) . "\n";
        
        echo "\nLast 100 characters (hex):\n";
        echo bin2hex(substr($output, -100)) . "\n";
    } else {
        echo "✅ VALID JSON\n";
        print_r($decoded);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}