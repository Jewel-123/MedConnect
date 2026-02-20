<?php
require_once 'db.php';

echo "--- Full Schema of 'appointments' table ---\n";
$res = $conn->query("DESC appointments");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} ({$row['Type']})\n";
}

echo "\n--- Data for Appointment 82 ---\n";
$res = $conn->query("SELECT * FROM appointments WHERE id = 82");
$appt = $res->fetch_assoc();
print_r($appt);

echo "\n--- Searching for symptoms in any consultation for patient {$appt['patient_id']} ---\n";
$res = $conn->query("SELECT * FROM consultations WHERE patient_id = {$appt['patient_id']} ORDER BY created_at DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Symptoms: {$row['symptoms']} | Created: {$row['created_at']} | Status: {$row['status']}\n";
}
?>
