<?php
// Debug: Check if any consultations exist that should show in incoming requests
require_once 'db.php';

echo "=== Checking Incoming Requests Issue ===\n\n";

// Get all doctors
$doctors = $conn->query("SELECT id, full_name FROM users WHERE role='doctor'")->fetch_all(MYSQLI_ASSOC);

foreach ($doctors as $doc) {
    $doctorId = $doc['id'];
    $doctorName = $doc['full_name'];
    
    echo "Doctor: $doctorName (ID: $doctorId)\n";
    echo str_repeat("-", 50) . "\n";
    
    // Check consultations for this doctor
    $query = "
        SELECT id, patient_id, status, payment_status, consultation_fee, symptoms
        FROM consultations 
        WHERE doctor_id = $doctorId
        ORDER BY created_at DESC
        LIMIT 5
    ";
    
    $results = $conn->query($query);
    
    if ($results->num_rows > 0) {
        while ($c = $results->fetch_assoc()) {
            echo "  Consultation #{$c['id']}\n";
            echo "    Status: '{$c['status']}'\n";
            echo "    Payment: '{$c['payment_status']}'\n";
            echo "    Fee: ₹{$c['consultation_fee']}\n";
            
            // Check if it SHOULD appear in incoming requests
            if ($c['status'] == 'pending' && $c['payment_status'] == 'paid') {
                echo "    ✅ SHOULD appear in incoming requests\n";
            } else {
                echo "    ❌ Will NOT appear (status='{$c['status']}', payment='{$c['payment_status']}')\n";
            }
            echo "\n";
        }
    } else {
        echo "  No consultations found\n\n";
    }
}

echo "\n=== What's Required for Incoming Requests ===\n";
echo "A consultation must have:\n";
echo "  - doctor_id = current doctor\n";
echo "  - status = 'pending'\n";
echo "  - payment_status = 'paid'\n";
