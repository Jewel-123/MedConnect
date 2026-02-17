<?php
require_once 'db.php';

echo "=== Searching for PAID, PENDING consultations that might be HIDDEN ===\n";
$res = $conn->query("
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.payment_status, c.matched_specialty, c.created_at,
           u.full_name as patient_name
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.payment_status = 'paid' AND c.status IN ('pending', 'assigned')
    ORDER BY c.created_at DESC
");

if ($res->num_rows === 0) {
    echo "No paid, pending consultations found.\n";
} else {
    while($row = $res->fetch_assoc()) {
        echo "Consultation ID: {$row['id']} | Patient: {$row['patient_name']} | Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . " | Status: {$row['status']} | Specialty: " . ($row['matched_specialty'] ?? 'NULL') . "\n";
    }
}

echo "\n=== Specialization Check for All Doctors ===\n";
$res = $conn->query("
    SELECT u.id, u.full_name, d.specialization 
    FROM users u 
    JOIN doctor_profiles d ON u.id = d.user_id 
    WHERE u.role = 'doctor'
");
while($row = $res->fetch_assoc()) {
    echo "Doctor ID: {$row['id']} | Name: {$row['full_name']} | Specialization: '{$row['specialization']}'\n";
}
?>
