<?php
require_once 'db.php';

$doctor_id = 25; // Emily Smith as seen in debug

echo "1. Checking get_active_consultations for Doctor 25...\n";
$_SESSION['user_id'] = $doctor_id;
$_SESSION['role'] = 'doctor';

// Simulate the API call
ob_start();
$_GET['action'] = 'get_active_consultations';
include 'doctor_api.php';
$output = ob_get_clean();
echo "API Result: " . $output . "\n\n";

echo "2. Marking Consultation 8 as in_progress for Doctor 25...\n";
$conn->query("UPDATE consultations SET doctor_id = 25, status = 'in_progress', assigned_at = NOW() WHERE id = 8");

echo "3. Re-checking get_active_consultations...\n";
ob_start();
include 'doctor_api.php';
$output = ob_get_clean();
echo "API Result: " . $output . "\n";