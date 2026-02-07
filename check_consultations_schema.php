<?php
require_once 'db.php';

echo "=== CHECKING CONSULTATIONS TABLE SCHEMA ===\n\n";

// Show consultations table structure
$result = $conn->query("DESCRIBE consultations");
echo "Current consultations table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']}) - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
}

echo "\n=== SAMPLE CONSULTATION DATA ===\n";
$consultations = $conn->query("
    SELECT c.*, u.full_name as patient_name, 
           pt.status as payment_status, pt.amount as payment_amount
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN payment_transactions pt ON pt.related_id = c.id AND pt.transaction_type = 'consultation_fee'
    LIMIT 5
");

while ($row = $consultations->fetch_assoc()) {
    echo "\nConsultation ID: {$row['id']}\n";
    echo "Patient: {$row['patient_name']}\n";
    echo "Status: {$row['status']}\n";
    echo "Payment Status: " . ($row['payment_status'] ?? 'N/A') . "\n";
    echo "Payment Amount: ₹" . ($row['payment_amount'] ?? 'N/A') . "\n";
    echo "Created: {$row['created_at']}\n";
    echo "---\n";
}