<?php
session_start();
require_once 'db.php';

// Simulate being logged in as doctor 19
$_SESSION['user_id'] = 19;
$_SESSION['role'] = 'doctor';
$doctor_id = 19;

// Run the updated query
$requests = $conn->query("
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.symptoms
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    LEFT JOIN patient_profiles p ON u.id = p.user_id
    WHERE c.status IN ('pending', 'reassigned') 
      AND (c.doctor_id IS NULL OR c.doctor_id = $doctor_id)
    ORDER BY c.created_at DESC
    LIMIT 5
");

echo "CONSULTATIONS FOR DOCTOR ID 19:\n";
$count = 0;
while ($row = $requests->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Patient: " . $row['patient_id'] . ", Doctor: " . ($row['doctor_id'] ?? 'NULL') . ", Status: " . $row['status'] . "\n";
    echo "Symptoms: " . substr($row['symptoms'], 0, 50) . "...\n\n";
    $count++;
}
echo "Total found: $count\n";
