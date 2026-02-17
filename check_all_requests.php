<?php
require_once 'db.php';

echo "=== RECENT CONSULTATIONS (ALL STATUSES) ===\n";
$res = $conn->query("
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.payment_status, c.symptoms, 
           u.full_name as patient_name, c.created_at,
           d.full_name as doctor_name
    FROM consultations c 
    JOIN users u ON c.patient_id = u.id 
    LEFT JOIN users d ON c.doctor_id = d.id
    ORDER BY c.id DESC LIMIT 10
");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "  Patient: {$row['patient_name']} (ID: {$row['patient_id']})\n";
    echo "  Doctor: " . ($row['doctor_name'] ?? 'Not assigned') . " (ID: " . ($row['doctor_id'] ?? 'NULL') . ")\n";
    echo "  Status: {$row['status']}\n";
    echo "  Payment: {$row['payment_status']}\n";
    echo "  Symptoms: {$row['symptoms']}\n";
    echo "  Created: {$row['created_at']}\n\n";
}

echo "=== RECENT APPOINTMENTS (ALL STATUSES) ===\n";
$res = $conn->query("
    SELECT a.id, a.patient_id, a.doctor_id, a.status, a.payment_status, a.reason,
           u.full_name as patient_name, a.scheduled_date, a.scheduled_time,
           d.full_name as doctor_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    LEFT JOIN users d ON a.doctor_id = d.id
    ORDER BY a.id DESC LIMIT 10
");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "  Patient: {$row['patient_name']} (ID: {$row['patient_id']})\n";
    echo "  Doctor: " . ($row['doctor_name'] ?? 'Not assigned') . " (ID: " . ($row['doctor_id'] ?? 'NULL') . ")\n";
    echo "  Status: {$row['status']}\n";
    echo "  Payment: {$row['payment_status']}\n";
    echo "  Reason: {$row['reason']}\n";
    echo "  Scheduled: {$row['scheduled_date']} {$row['scheduled_time']}\n\n";
}
?>
