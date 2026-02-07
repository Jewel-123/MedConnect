<?php
// Test accept consultation API
session_start();
require_once 'db.php';

$_SESSION['user_id'] = 25;  // Doctor Emily Smith
$_SESSION['role'] = 'doctor';
$_SESSION['full_name'] = 'Emily Smith';

echo "=== Testing Accept Consultation API ===\n\n";

// Find a pending paid consultation
$consultation = $conn->query("
    SELECT id FROM consultations 
    WHERE doctor_id = 25 AND status = 'pending' AND payment_status = 'paid'
    LIMIT 1
")->fetch_assoc();

if (!$consultation) {
    echo "No pending paid consultations found for doctor 25\n";
    exit;
}

$consultation_id = $consultation['id'];
echo "Testing with consultation ID: $consultation_id\n\n";

// Test the API
$_POST['action'] = 'accept_consultation';
$_POST['consultation_id'] = $consultation_id;

ob_start();
include 'doctor_api.php';
$response = ob_get_clean();

echo "API Response:\n";
echo $response . "\n\n";

// Verify database was updated
$updated = $conn->query("
    SELECT status, start_time, assigned_at 
    FROM consultations 
    WHERE id = $consultation_id
")->fetch_assoc();

echo "Consultation status after accept:\n";
echo "  - Status: {$updated['status']}\n";
echo "  - Start time: {$updated['start_time']}\n";
echo "  - Assigned at: {$updated['assigned_at']}\n";
