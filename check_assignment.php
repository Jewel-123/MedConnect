<?php
require_once 'db.php';

// Check most recent consultation
$res = $conn->query("SELECT id, patient_id, doctor_id, status, matched_specialty, created_at FROM consultations ORDER BY created_at DESC LIMIT 1");
echo "MOST RECENT CONSULTATION:\n";
if ($res && $row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Patient ID: " . $row['patient_id'] . "\n";
    echo "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Specialty: " . ($row['matched_specialty'] ?? 'NULL') . "\n";
    echo "Created: " . $row['created_at'] . "\n\n";
} else {
    echo "No consultations found\n\n";
}

// Check what the API would return
$res = $conn->query("
    SELECT COUNT(*) as count 
    FROM consultations 
    WHERE status = 'pending' AND (doctor_id IS NULL OR doctor_id = 1)
");
$count = $res->fetch_assoc()['count'];
echo "Consultations that WOULD show for doctor_id=1: $count\n";

// Check unassigned only
$res = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE status = 'pending' AND doctor_id IS NULL");
$unassigned = $res->fetch_assoc()['count'];
echo "Unassigned pending: $unassigned\n";