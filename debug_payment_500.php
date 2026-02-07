<?php
// Enable all error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/payment_debug.log');

echo "=== Payment API Debug Test ===\n\n";

// Start session and set user
session_start();
$_SESSION['user_id'] = 24; // Test patient
$_SESSION['role'] = 'patient';

// Prepare POST data
$_POST['action'] = 'initiate_payment';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create JSON input
$jsonData = json_encode([
    'transaction_type' => 'consultation_fee',
    'related_id' => 18,
    'amount' => 200,
    'payment_method' => 'card'
]);

// Mock php://input
file_put_contents('php://input', $jsonData);

// Capture all output
ob_start();

try {
    echo "Including payment_api.php...\n";
    include 'payment_api.php';
    $output = ob_get_clean();
    
    echo "\n=== RAW OUTPUT ===\n";
    echo $output;
    echo "\n=== END OUTPUT ===\n";
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "\n❌ FATAL ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString() . "\n";
}

// Check debug log
if (file_exists('payment_debug.log')) {
    echo "\n=== DEBUG LOG ===\n";
    echo file_get_contents('payment_debug.log');
}
