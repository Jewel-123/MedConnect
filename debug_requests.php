<?php
require_once 'db.php';
$res = $conn->query("SELECT id, doctor_id, patient_id, status, payment_status, created_at FROM consultations WHERE payment_status = 'paid' AND status NOT IN ('completed', 'cancelled')");
echo "ID | Doctor | Patient | Status | Payment | Created\n";
while($row = $res->fetch_assoc()){
    echo "{$row['id']} | {$row['doctor_id']} | {$row['patient_id']} | {$row['status']} | {$row['payment_status']} | {$row['created_at']}\n";
}
?>
