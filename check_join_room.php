<?php
// Check what consultation ID 18 has
require_once 'db.php';

echo "=== Checking Consultation #18 ===\n\n";

$consult = $conn->query("SELECT * FROM consultations WHERE id = 18")->fetch_assoc();

if ($consult) {
    echo "Consultation Details:\n";
    echo "  ID: {$consult['id']}\n";
    echo "  Patient: {$consult['patient_id']}\n";
    echo "  Doctor: {$consult['doctor_id']}\n";
    echo "  Status: '{$consult['status']}'\n";
    echo "  Payment Status: '{$consult['payment_status']}'\n";
    echo "  Symptoms: " . substr($consult['symptoms'], 0, 50) . "...\n\n";
    
    echo "Join Room URL should be: consultation_room.php?id=18\n";
    echo "✅ This should work now that consultation exists and has proper status\n";
} else {
    echo "❌ Consultation #18 not found\n";
}
