<?php
// Find the dermatologist payment and check consultation creation
require_once 'db.php';

echo "=== Investigating Dermatologist Payment ===\n\n";

// Find dermatologist (Sophia Martinez)
$derm = $conn->query("
    SELECT u.id, u.full_name, dp.specialization
    FROM users u
    JOIN doctor_profiles dp ON u.id = dp.user_id
    WHERE dp.specialization LIKE '%Dermatologist%'
")->fetch_assoc();

if ($derm) {
    echo "Dermatologist: {$derm['full_name']} (ID: {$derm['id']})\n\n";
    $doc_id = $derm['id'];
    
    // Find recent payments for this doctor
    echo "Recent payments for this doctor:\n";
    $payments = $conn->query("
        SELECT id, transaction_number, user_id, related_id, amount, status, created_at
        FROM payment_transactions
        WHERE doctor_id = $doc_id
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    while ($p = $payments->fetch_assoc()) {
        echo "\n  Payment #{$p['id']} - {$p['transaction_number']}\n";
        echo "    User: {$p['user_id']}, Amount: {$p['amount']}, Status: {$p['status']}\n";
        echo "    Related consultation ID: {$p['related_id']}\n";
        echo "    Created: {$p['created_at']}\n";
        
        // Check if consultation exists
        if ($p['related_id']) {
            $consult = $conn->query("
                SELECT id, doctor_id, status, payment_status, consultation_fee
                FROM consultations
                WHERE id = {$p['related_id']}
            ")->fetch_assoc();
            
            if ($consult) {
                echo "    → Consultation EXISTS:\n";
                echo "       Doctor ID: {$consult['doctor_id']}\n";
                echo "       Status: '{$consult['status']}'\n";
                echo "       Payment: '{$consult['payment_status']}'\n";
                echo "       Fee: {$consult['consultation_fee']}\n";
                
                // Check if it should appear in incoming requests
                if ($consult['doctor_id'] == $doc_id && $consult['status'] == 'pending' && $consult['payment_status'] == 'paid') {
                    echo "       ✅ SHOULD appear in incoming requests\n";
                } else {
                    echo "       ❌ Will NOT appear because:\n";
                    if ($consult['doctor_id'] != $doc_id) echo "          - Wrong doctor_id ({$consult['doctor_id']} != $doc_id)\n";
                    if ($consult['status'] != 'pending') echo "          - Status is '{$consult['status']}' (needs 'pending')\n";
                    if ($consult['payment_status'] != 'paid') echo "          - Payment is '{$consult['payment_status']}' (needs 'paid')\n";
                }
            } else {
                echo "    → ❌ Consultation DOES NOT EXIST!\n";
            }
        }
    }
}

// Check all pending+paid consultations
echo "\n\n=== All Pending + Paid Consultations ===\n";
$all = $conn->query("
    SELECT c.id, c.doctor_id, u.full_name as doctor, c.patient_id, p.full_name as patient,
           c.status, c.payment_status, c.consultation_fee, c.created_at
    FROM consultations c
    LEFT JOIN users u ON c.doctor_id = u.id
    LEFT JOIN users p ON c.patient_id = p.id
    WHERE c.status = 'pending' AND c.payment_status = 'paid'
    ORDER BY c.created_at DESC
");

if ($all && $all->num_rows > 0) {
    while ($row = $all->fetch_assoc()) {
        echo "ID:{$row['id']}, Doctor:{$row['doctor']} (ID:{$row['doctor_id']}), Patient:{$row['patient']}, Fee:{$row['consultation_fee']}, Created:{$row['created_at']}\n";
    }
} else {
    echo "No pending+paid consultations found in database\n";
}
