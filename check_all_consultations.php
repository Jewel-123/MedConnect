<?php
// Get complete picture of consultations
require_once 'db.php';

echo "=== Current Consultation State for All Doctors ===\n\n";

$all = $conn->query("
    SELECT c.id, c.doctor_id, u.full_name as patient_name, c.status, c.payment_status, 
           c.consultation_fee, c.created_at
    FROM consultations c
    JOIN users u ON c.patient_id = u.id
    WHERE c.payment_status = 'paid'
    ORDER BY c.created_at DESC
    LIMIT 20
");

echo "All PAID consultations:\n";
while ($row = $all->fetch_assoc()) {
    $docId = $row['doctor_id'] ?: 'NULL';
    echo "  ID:{$row['id']}, Patient:{$row['patient_name']}, Doctor:$docId, Status:'{$row['status']}', Fee:{$row['consultation_fee']}, Created:{$row['created_at']}\n";
}

echo "\n=== Consultations by Doctor ===\n";
$byDoctor = $conn->query("
    SELECT doctor_id, status, payment_status, COUNT(*) as count
    FROM consultations
    GROUP BY doctor_id, status, payment_status
    ORDER BY doctor_id, status
");

while ($row = $byDoctor->fetch_assoc()) {
    $docId = $row['doctor_id'] ?: 'UNASSIGNED';
    echo "Doctor:$docId, Status:'{$row['status']}', Payment:'{$row['payment_status']}' => {$row['count']}\n";
}
