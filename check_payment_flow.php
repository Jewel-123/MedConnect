<?php
// Check recent payment transactions to find the user's booking
require_once 'db.php';

echo "=== Recent Payment Transactions ===\n\n";

$payments = $conn->query("
    SELECT pt.id, pt.transaction_number, pt.user_id, u.full_name, 
           pt.transaction_type, pt.related_id, pt.doctor_id, pt.amount, 
           pt.status, pt.created_at
    FROM payment_transactions pt
    JOIN users u ON pt.user_id = u.id
    ORDER BY pt.created_at DESC
    LIMIT 10
");

while ($row = $payments->fetch_assoc()) {
    echo "\nTransaction #{$row['id']} - {$row['transaction_number']}\n";
    echo "  User: {$row['full_name']} (ID: {$row['user_id']})\n";
    echo "  Type: {$row['transaction_type']}\n";
    echo "  Related ID: {$row['related_id']}\n";
    echo "  Doctor ID: {$row['doctor_id']}\n";
    echo "  Amount: \${$row['amount']}\n";
    echo "  Status: {$row['status']}\n";
    echo "  Created: {$row['created_at']}\n";
    
    // Check if related consultation exists
    if ($row['related_id']) {
        $consult = $conn->query("
            SELECT id, status, payment_status, doctor_id, consultation_fee 
            FROM consultations 
            WHERE id = {$row['related_id']}
        ")->fetch_assoc();
        
        if ($consult) {
            echo "  → Consultation exists: ID {$consult['id']}, Doctor: {$consult['doctor_id']}, Status: '{$consult['status']}', Payment: '{$consult['payment_status']}', Fee: {$consult['consultation_fee']}\n";
        } else {
            echo "  → ❌ Consultation #{$row['related_id']} NOT FOUND!\n";
        }
    }
}

echo "\n\n=== Checking for Broken Consultations ===\n";
$broken = $conn->query("
    SELECT id, patient_id, doctor_id, status, payment_status, consultation_fee, created_at
    FROM consultations
    WHERE (doctor_id IS NULL OR doctor_id = 0 OR consultation_fee = 0)
      AND payment_status = 'paid'
    ORDER BY created_at DESC
    LIMIT 10
");

if ($broken && $broken->num_rows > 0) {
    echo "Found {$broken->num_rows} broken consultation record(s):\n";
    while ($row = $broken->fetch_assoc()) {
        echo "  ID: {$row['id']}, Doctor: " . ($row['doctor_id'] ?: 'NULL') . ", Fee: {$row['consultation_fee']}, Status: '{$row['status']}', Created: {$row['created_at']}\n";
    }
} else {
    echo "No broken consultations found\n";
}
