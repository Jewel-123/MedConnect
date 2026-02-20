<?php
require_once 'db.php';

$patientId = 13; // JEWEL BIJU
$res = $conn->query("SELECT * FROM consultations WHERE patient_id = $patientId ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Status: " . $row['status'] . " | Symptoms: " . $row['symptoms'] . " | Created: " . $row['created_at'] . "\n";
}

echo "\n--- Latest Appointments for Patient 13 ---\n";
$res = $conn->query("SELECT * FROM appointments WHERE patient_id = $patientId ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Status: " . $row['status'] . " | Notes: " . $row['notes'] . " | Created: " . $row['created_at'] . "\n";
}
?>
