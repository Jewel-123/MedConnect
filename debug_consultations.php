<?php
require_once 'db.php';

echo "=== CONSULTATIONS FROM JEWEL BIJU ===\n";
$res = $conn->query("
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.symptoms, u.full_name as patient_name, c.payment_status 
    FROM consultations c 
    JOIN users u ON c.patient_id = u.id 
    WHERE u.full_name LIKE '%Jewel%' OR u.full_name LIKE '%Biju%' 
    ORDER BY c.id DESC LIMIT 3
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
}

echo "\n=== DR. EMILY SMITH INFO ===\n";
$res = $conn->query("SELECT id, full_name, role FROM users WHERE full_name LIKE '%Emily%Smith%' AND role = 'doctor'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== ACTIVE CONSULTATIONS FOR DR. EMILY (ID 25) ===\n";
$res = $conn->query("
    SELECT c.id, c.patient_id, c.doctor_id, c.status, c.symptoms, u.full_name as patient_name 
    FROM consultations c 
    JOIN users u ON c.patient_id = u.id 
    WHERE c.doctor_id = 25 AND c.status IN ('accepted', 'in_progress', 'paused')
    ORDER BY c.id DESC
");
while ($row = $res->fetch_assoc()) {
    print_r($row);
    echo "\n";
}
?>
