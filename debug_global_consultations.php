<?php
require_once 'db.php';

$res = $conn->query("SELECT c.*, u.full_name FROM consultations c JOIN users u ON c.patient_id = u.id ORDER BY c.id DESC LIMIT 20");
echo "--- Global Consultations ---\n";
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Patient: " . $row['full_name'] . " | Symptoms: " . $row['symptoms'] . " | Created: " . $row['created_at'] . "\n";
}
?>
