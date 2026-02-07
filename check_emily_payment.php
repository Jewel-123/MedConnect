<?php
// Check if Emily Smith made a payment but consultation wasn't created
require_once 'db.php';

echo "=== Investigating Emily Smith Payment ===\n\n";

// Find Emily Smith user
$emily = $conn->query("
    SELECT id, full_name, email FROM users WHERE full_name LIKE '%Emily%Smith%'
")->fetch_assoc();

if (!$emily) {
    echo "Emily Smith user not found!\n";
    exit;
}

echo "Emily Smith: ID {$emily['id']}, Email: {$emily['email']}\n\n";

// Check payment transactions
echo "Payment transactions for Emily Smith:\n";
$payments = $conn->query("
    SELECT id, transaction_number, transaction_type, related_id, amount, status, created_at
    FROM payment_transactions
    WHERE user_id = {$emily['id']}
    ORDER BY created_at DESC
    LIMIT 5
");

if ($payments && $payments->num_rows > 0) {
    while ($p = $payments->fetch_assoc()) {
        echo "  - ID: {$p['id']}, Type: {$p['transaction_type']}, Related ID: {$p['related_id']}, Amount: {$p['amount']}, Status: {$p['status']}, Created: {$p['created_at']}\n";
        
        // Check if related consultation exists
        if ($p['related_id']) {
            $consult = $conn->query("SELECT id, status, payment_status, doctor_id FROM consultations WHERE id = {$p['related_id']}")->fetch_assoc();
            if ($consult) {
                echo "      → Consultation #{$consult['id']} exists: Doctor {$consult['doctor_id']}, Status: '{$consult['status']}', Payment: '{$consult['payment_status']}'\n";
            } else {
                echo "      → ❌ Consultation #{$p['related_id']} NOT FOUND!\n";
            }
        }
    }
} else {
    echo "  No payment transactions found\n";
}

// Check consultations
echo "\nConsultations for Emily Smith:\n";
$consults = $conn->query("
    SELECT id, doctor_id, status, payment_status, consultation_fee, created_at
    FROM consultations
    WHERE patient_id = {$emily['id']}
    ORDER BY created_at DESC
");

if ($consults && $consults->num_rows > 0) {
    while ($c = $consults->fetch_assoc()) {
        echo "  - ID: {$c['id']}, Doctor: {$c['doctor_id']}, Status: '{$c['status']}', Payment: '{$c['payment_status']}', Fee: {$c['consultation_fee']}, Created: {$c['created_at']}\n";
    }
} else {
    echo "  No consultations found\n";
}
