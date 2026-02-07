<?php
// Quick error check for doctor_api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING DOCTOR API SYNTAX ===\n\n";

// Check if file has syntax errors
$output = [];
$return_var = 0;
exec('C:\xampp\php\php.exe -l doctor_api.php 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "✅ No syntax errors in doctor_api.php\n";
} else {
    echo "❌ SYNTAX ERRORS FOUND:\n";
    foreach ($output as $line) {
        echo "$line\n";
    }
}

echo "\n=== CHECKING RECENT ERROR LOGS ===\n";
if (file_exists('doctor_api_errors.log')) {
    $errors = file_get_contents('doctor_api_errors.log');
    $lines = explode("\n", $errors);
    $recent = array_slice($lines, -50);
    echo implode("\n", $recent);
} else {
    echo "No error log file found.\n";
}

echo "\n=== TESTING BASIC API CALL ===\n";
// Simulate a request
$_SESSION['user_id'] = 25; // Emily Smith
$_SESSION['role'] = 'doctor';

echo "Session set for Doctor ID 25\n";