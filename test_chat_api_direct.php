<?php
/**
 * Test chat_api.php directly with simulated session
 */

session_start();
require_once 'db.php';

// Simulate being logged in
$_SESSION['user_id'] = 2; // Doctor
$_SESSION['role'] = 'doctor';

echo "Testing chat_api.php fetch...\n\n";

// Set up GET parameters
$_GET['action'] = 'fetch';
$_GET['consultation_id'] = 7;
$_GET['last_id'] = 0;

echo "Session user_id: " . $_SESSION['user_id'] . "\n";
echo "Consultation ID: 7\n";
echo "Last ID: 0\n\n";

// Execute the API
ob_start();
try {
    include 'chat_api.php';
    $output = ob_get_clean();
    echo "API Output:\n";
    echo $output . "\n\n";
    
    $data = json_decode($output, true);
    if ($data) {
        echo "Parsed JSON:\n";
        print_r($data);
    } else {
        echo "Failed to parse JSON\n";
    }
} catch (Exception $e) {
    ob_get_clean();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
