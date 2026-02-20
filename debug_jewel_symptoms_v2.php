<?php
require_once 'db.php';

$res = $conn->query("SELECT a.id, a.notes, a.created_at FROM appointments a JOIN users u ON a.patient_id = u.id WHERE u.full_name LIKE '%Jewel%' ORDER BY a.id DESC LIMIT 3");
while ($row = $res->fetch_assoc()) {
    echo "Appt ID: " . $row['id'] . " | Notes: " . $row['notes'] . " | Created: " . $row['created_at'] . "\n";
}

$res = $conn->query("SELECT c.id, c.symptoms, c.created_at FROM consultations c JOIN users u ON c.patient_id = u.id WHERE u.full_name LIKE '%Jewel%' ORDER BY c.id DESC LIMIT 3");
while ($row = $res->fetch_assoc()) {
    echo "Cons ID: " . $row['id'] . " | Symptoms: " . $row['symptoms'] . " | Created: " . $row['created_at'] . "\n";
}
?>
