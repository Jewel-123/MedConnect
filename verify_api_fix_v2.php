<?php
require_once 'db.php';

// Mock session
session_start();
$_SESSION['user_id'] = 1; 
$_SESSION['role'] = 'doctor';

echo "=== Verifying get_active_consultations (patient_id check) ===\n";

$doctor_id = 1;

// This logic matches get_active_consultations in doctor_api.php (post-fix)
$query = "
    (SELECT c.id, c.patient_id, u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.doctor_id = $doctor_id 
      AND c.status IN ('accepted', 'confirmed', 'in_progress', 'paused'))
    
    UNION ALL
    
    (SELECT a.id, a.patient_id, u.full_name as patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE a.doctor_id = $doctor_id 
      AND a.status IN ('confirmed', 'in_progress', 'paused'))
";

$res = $conn->query($query);
if ($res) {
    echo "Query successful. Checking rows...\n";
    $found = false;
    while ($row = $res->fetch_assoc()) {
        $found = true;
        echo "ID: " . $row['id'] . " | Patient Name: " . $row['patient_name'] . " | Patient ID: " . ($row['patient_id'] ?? 'MISSING') . "\n";
    }
    if (!$found) echo "No active consultations/appointments found for doctor #1. Try a different doctor ID if needed.\n";
} else {
    echo "Query failed: " . $conn->error . "\n";
}
?>
