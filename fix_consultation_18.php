<?php
// Fix consultation #18 which has empty status
require_once 'db.php';

echo "=== Fixing Consultation #18 ===\n\n";

// Check current state
$consult = $conn->query("SELECT * FROM consultations WHERE id = 18")->fetch_assoc();

if ($consult) {
    echo "Current state:\n";
    echo "  ID: {$consult['id']}\n";
    echo "  Doctor ID: {$consult['doctor_id']}\n";
    echo "  Patient ID: {$consult['patient_id']}\n";
    echo "  Status: '{$consult['status']}'\n";
    echo "  Payment: '{$consult['payment_status']}'\n";
    echo "  Fee: {$consult['consultation_fee']}\n\n";
    
    // Fix it
    $result = $conn->query("
        UPDATE consultations
        SET status = 'pending',
            consultation_fee = 300.00
        WHERE id = 18
    ");
    
    if ($result) {
        echo "✅ Fixed consultation #18\n";
        echo "  - Set status to 'pending'\n";
        echo "  - Set consultation_fee to 300.00\n\n";
        
        // Verify
        $verify = $conn->query("SELECT id, doctor_id, status, payment_status, consultation_fee FROM consultations WHERE id = 18")->fetch_assoc();
        echo "New state:\n";
        echo "  Status: '{$verify['status']}'\n";
        echo "  Fee: {$verify['consultation_fee']}\n\n";
        
        echo "✅ This consultation should now appear in Dr. Sophia Martinez's incoming requests!\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
} else {
    echo "Consultation #18 not found\n";
}
