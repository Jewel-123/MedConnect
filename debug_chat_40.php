<?php
require_once 'db.php';
$id = 40;
echo "=== Messages for Consultation $id ===\n";
$res = $conn->query("SELECT * FROM messages WHERE consultation_id = $id ORDER BY id ASC");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "\n=== Consultation Details ===\n";
$res = $conn->query("SELECT id, patient_id, doctor_id, status FROM consultations WHERE id = $id");
print_r($res->fetch_assoc());
