<?php
require_once 'db.php';

echo "--- Appointments for Jewel Biju (patient_id likely) ---\n";
$res = $conn->query("SELECT a.*, u.full_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE u.full_name LIKE '%Jewel%' ORDER BY a.id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Consultations for Jewel Biju ---\n";
$res = $conn->query("SELECT c.*, u.full_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE u.full_name LIKE '%Jewel%' ORDER BY c.id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
