<?php
require_once 'db.php';

$res = $conn->query("SELECT id, patient_id, doctor_id, status, urgency_level, created_at FROM consultations WHERE id = 55");
if ($res && $row = $res->fetch_assoc()) {
    echo "CONSULTATION 55:\n";
    foreach ($row as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "Consultation 55 not found\n";
}

// Check all recent consultations
echo "\n\nALL RECENT CONSULTATIONS:\n";
$res = $conn->query("SELECT id, doctor_id, status FROM consultations ORDER BY created_at DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}, Doctor: " . ($row['doctor_id'] ?? 'NULL') . ", Status: {$row['status']}\n";
}