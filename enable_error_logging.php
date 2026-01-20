<?php
/**
 * Enable PHP error logging and display for debugging
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

// Set custom error log location
$error_log_path = __DIR__ . '/debug_errors.log';
ini_set('error_log', $error_log_path);

echo "Error logging enabled!\n";
echo "Error log location: " . $error_log_path . "\n";
echo "Current error_log setting: " . ini_get('error_log') . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
echo "\n";

// Test error logging
error_log("TEST: Error logging is working - " . date('Y-m-d H:i:s'));

if (file_exists($error_log_path)) {
    echo "✅ Error log file exists and is writable\n";
    echo "\nRecent error log contents:\n";
    echo "-----------------------------------\n";
    echo file_get_contents($error_log_path);
} else {
    echo "⚠️ Error log file doesn't exist yet. It will be created when an error occurs.\n";
}
