<?php
// Fix consultation with empty status
require_once 'db.php';

echo "=== Fixing Consultations with Empty Status ===\n\n";

// Find consultations with empty/NULL status that should be 'pending'
$empty = $conn->query("
    SELECT id, patient_id, doctor_id, payment_status, created_at
    FROM consultations
    WHERE (status IS NULL OR status = '')
      AND payment_status = 'paid'
");

if ($empty && $empty->num_rows > 0) {
    echo "Found {$empty->num_rows} consultation(s) with empty status:\n";
    
    while ($row = $empty->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Doctor: {$row['doctor_id']}, Payment: {$row['payment_status']}\n";
    }
    
    // Fix them
    $result = $conn->query("
        UPDATE consultations
        SET status = 'pending'
        WHERE (status IS NULL OR status = '')
          AND payment_status = 'paid'
    ");
    
    if ($result) {
        echo "\n✅ Fixed {$conn->affected_rows} consultation(s) - set status to 'pending'\n";
    } else {
        echo "\n❌ Error fixing consultations: " . $conn->error . "\n";
    }
} else {
    echo "No consultations with empty status found\n";
}

// Check result
echo "\n=== Verifying Fix ===\n";
$verify = $conn->query("
    SELECT id, doctor_id, status, payment_status
    FROM consultations
    WHERE doctor_id = 25 AND payment_status = 'paid'
");

if ($verify && $verify->num_rows > 0) {
    echo "Consultations for doctor 25 with paid status:\n";
    while ($row = $verify->fetch_assoc()) {
        echo "  - ID: {$row['id']}, Status: '{$row['status']}', Payment: '{$row['payment_status']}'\n";
    }
} else {
    echo "No paid consultations for doctor 25\n";
}
