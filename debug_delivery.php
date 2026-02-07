<?php
require_once 'db.php';

$output = "=== DEBUG CONSULTATION DELIVERY ===\n\n";

$res = $conn->query("SELECT c.id, c.patient_id, c.doctor_id, c.status, c.payment_status, c.created_at, u.full_name as patient_name 
                    FROM consultations c 
                    JOIN users u ON c.patient_id = u.id 
                    ORDER BY c.created_at DESC LIMIT 10");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $output .= "ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: " . ($row['doctor_id'] ?: 'NULL') . " | Status: {$row['status']} | Payment: {$row['payment_status']} | Created: {$row['created_at']}\n";
    }
} else {
    $output .= "Error fetching consultations: " . $conn->error . "\n";
}

$output .= "\n=== DOCTOR ASSIGNMENT CHECK ===\n";
$res = $conn->query("SELECT id, full_name, role, status FROM users WHERE role = 'doctor' AND status = 'approved' LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $output .= "Doctor ID: {$row['id']} | Name: {$row['full_name']} | Status: {$row['status']}\n";
    }
}

file_put_contents('debug_output_clean.txt', $output);
echo "Debug output written to debug_output_clean.txt\n";
