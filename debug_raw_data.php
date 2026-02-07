<?php
require_once 'db.php';

$output = "=== COMPREHENSIVE CONSULTATIONS CHECK ===\n\n";

// Check all consultations without filters to find what the user booked
$result = $conn->query("
    SELECT c.*, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 10
");

while ($row = $result->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Patient: {$row['patient_name']} | DocID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']} | Payment: {$row['payment_status']} | Mode: {$row['consultation_mode']} | Created: {$row['created_at']}\n";
}

$output .= "\n=== APPOINTMENTS CHECK ===\n\n";
$result = $conn->query("
    SELECT a.*, u.full_name as patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 10
");

while ($row = $result->fetch_assoc()) {
    $output .= "ID: {$row['id']} | Patient: {$row['patient_name']} | DocID: {$row['doctor_id']} | Status: {$row['status']} | Payment: {$row['payment_status']} | Date: {$row['scheduled_date']} | Created: {$row['created_at']}\n";
}

file_put_contents('debug_raw_data.txt', $output);
echo "Raw data debug written to debug_raw_data.txt";