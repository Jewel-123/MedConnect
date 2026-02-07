<?php
require_once 'db.php';

$output = "=== FINAL VERIFICATION OF PENDING PAID REQUESTS ===\n\n";

// 1. Pending Paid Consultations
$output .= "--- PENDING PAID CONSULTATIONS ---\n";
$query = "
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.payment_status, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.status = 'pending' AND c.payment_status = 'paid'
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']}\n";
}
$output .= "\n";

// 2. Pending Paid Appointments
$output .= "--- PENDING PAID APPOINTMENTS ---\n";
$query = "
    SELECT a.id, a.patient_id, a.doctor_id, a.status, a.payment_status, u.full_name as patient_name, a.scheduled_date, a.scheduled_time
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.status = 'pending' AND a.payment_status = 'paid'
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: {$row['doctor_id']} | Status: {$row['status']} | Time: {$row['scheduled_date']} {$row['scheduled_time']}\n";
}
$output .= "\n";

// 3. Transactions check
$output .= "--- RECENT COMPLETED TRANSACTIONS ---\n";
$query = "
    SELECT id, transaction_number, transaction_type, related_id, related_type, doctor_id, status
    FROM payment_transactions
    ORDER BY created_at DESC LIMIT 10
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Type: {$row['transaction_type']} | RelID: {$row['related_id']} | RelType: {$row['related_type']} | DocID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']}\n";
}

file_put_contents('final_verification.txt', $output);
echo "Verification written to final_verification.txt";