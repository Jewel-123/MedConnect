<?php
session_start();
require_once 'db.php';

echo "<!DOCTYPE html><html><head><title>Debug</title></head><body>";
echo "<h1>Debug Info</h1>";

// Check session
echo "<h2>Session Data:</h2><pre>";
print_r($_SESSION);
echo "</pre>";

// Check if any errors are stored
if (isset($_SESSION['error'])) {
    echo "<p style='color: red;'>Session Error: " . $_SESSION['error'] . "</p>";
}

// Check appointment_booking.php
echo "<h2>Checking appointment_booking.php output:</h2>";
ob_start();
include 'appointment_booking.php';
$output = ob_get_clean();

// Find if there's any alert in the output
if (strpos($output, 'Doctor is not available') !== false) {
    echo "<p style='color: red;'>ERROR FOUND IN HTML OUTPUT!</p>";
    // Extract the alert section
    $start = strpos($output, 'alertBox');
    echo "<pre>" . htmlspecialchars(substr($output, max(0, $start - 100), 300)) . "</pre>";
} else {
    echo "<p style='color: green;'>No error found in HTML output</p>";
}

echo "</body></html>";