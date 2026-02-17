<?php
require_once 'db.php';

// Simulate doctor session if needed, but let's just list all doctors first
echo "=== Doctor Profiles ===\n";
$res = $conn->query("SELECT u.id, u.full_name, d.specialization FROM users u JOIN doctor_profiles d ON u.id = d.user_id");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['full_name']} | Specialty: [{$row['specialization']}]\n";
}

echo "\n=== Recent Consultations (Pending/Assigned) ===\n";
$res = $conn->query("SELECT id, doctor_id, matched_specialty, status, payment_status, created_at FROM consultations WHERE status IN ('pending', 'assigned') ORDER BY created_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | DocID: " . ($row['doctor_id'] ?? 'NULL') . " | Specialty: [{$row['matched_specialty']}] | Status: {$row['status']} | Payment: {$row['payment_status']} | Created: {$row['created_at']}\n";
}

echo "\n=== Recent Appointments (Pending/Confirmed) ===\n";
$res = $conn->query("SELECT id, doctor_id, status, payment_status, scheduled_date FROM appointments WHERE status IN ('pending', 'confirmed') ORDER BY created_at DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | DocID: {$row['doctor_id']} | Status: {$row['status']} | Payment: {$row['payment_status']} | Date: {$row['scheduled_date']}\n";
}

$conn->close();
?>
