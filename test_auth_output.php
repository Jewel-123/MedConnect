<?php
// Simulate a login request to auth.php
$_POST['action'] = 'login';
$_POST['email'] = 'admin@medconnect.com';
$_POST['password'] = 'admin123';

ob_start();
include 'auth.php';
$output = ob_get_clean();

$json = json_decode($output, true);

echo "--- RAW OUTPUT ---\n";
echo $output;
echo "\n--- END RAW OUTPUT ---\n";

if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON IS VALID!\n";
    print_r($json);
} else {
    echo "JSON IS INVALID! Error: " . json_last_error_msg() . "\n";
    // Check for leading whitespace/content
    if (strlen($output) > 0 && $output[0] !== '{') {
        echo "Found leading content before JSON: [" . substr($output, 0, 50) . "...]\n";
    }
}