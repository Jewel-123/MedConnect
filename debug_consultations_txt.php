<?php
require_once 'db.php';

$output = "=== CONSULTATIONS TABLE DEBUG ===\n\n";

// Check all consultations
$result = $conn->query("
    SELECT 
        c.id,
        c.patient_id,
        c.doctor_id,
        c.status,
        c.payment_status,
        c.consultation_fee,
        c.symptoms,
        c.created_at,
        u.full_name as patient_name,
        d.full_name as doctor_name
    FROM consultations c
    LEFT JOIN users u ON c.patient_id = u.id
    LEFT JOIN users d ON c.doctor_id = d.id
    ORDER BY c.created_at DESC
    LIMIT 20
");

$output .= "=== RECENT CONSULTATIONS (Last 20) ===\n\n";
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    $output .= "Consultation #" . $row['id'] . "\n";
    $output .= "  Patient: " . $row['patient_name'] . " (ID: " . $row['patient_id'] . ")\n";
    $output .= "  Doctor: " . ($row['doctor_name'] ?? 'Not Assigned') . " (ID: " . ($row['doctor_id'] ?? 'NULL') . ")\n";
    $output .= "  Status: " . $row['status'] . "\n";
    $output .= "  Payment Status: " . ($row['payment_status'] ?? 'NULL') . "\n";
    $output .= "  Fee: ₹" . $row['consultation_fee'] . "\n";
    $output .= "  Symptoms: " . substr($row['symptoms'], 0, 100) . "...\n";
    $output .= "  Created: " . $row['created_at'] . "\n";
    $output .= "  ---\n\n";
}
$output .= "Total consultations: $count\n\n";

// Check pending paid consultations
$output .= "=== PENDING PAID CONSULTATIONS (Should appear in Incoming Requests) ===\n\n";
$pendingResult = $conn->query("
    SELECT 
        c.id,
        c.patient_id,
        c.doctor_id,
        c.status,
        c.payment_status,
        c.created_at,
        u.full_name as patient_name
    FROM consultations c
    LEFT JOIN users u ON c.patient_id = u.id
    WHERE c.status = 'pending' AND c.payment_status = 'paid'
    ORDER BY c.created_at DESC
");

$pendingCount = 0;
while ($row = $pendingResult->fetch_assoc()) {
    $pendingCount++;
    $output .= "Consultation #" . $row['id'] . "\n";
    $output .= "  Patient: " . $row['patient_name'] . "\n";
    $output .= "  Doctor ID: " . ($row['doctor_id'] ?? 'NULL (available to all doctors)') . "\n";
    $output .= "  Status: " . $row['status'] . "\n";
    $output .= "  Payment Status: " . $row['payment_status'] . "\n";
    $output .= "  Created: " . $row['created_at'] . "\n";
    $output .= "  ---\n\n";
}
$output .= "Pending paid consultations count: $pendingCount\n\n";

// Check table schema
$output .= "=== CONSULTATIONS TABLE SCHEMA ===\n\n";
$schema = $conn->query("DESCRIBE consultations");
while ($col = $schema->fetch_assoc()) {
    $output .= sprintf("%-30s %-20s %-10s %s\n", 
        $col['Field'], 
        $col['Type'], 
        $col['Null'], 
        $col['Default'] ?? 'NULL'
    );
}

file_put_contents('debug_consultations_output.txt', $output);
echo "Debug output written to debug_consultations_output.txt";