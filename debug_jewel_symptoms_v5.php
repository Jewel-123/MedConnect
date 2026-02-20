<?php
require_once 'db.php';

$output = "";
$patientId = 13; // JEWEL BIJU

$res = $conn->query("SELECT * FROM consultations WHERE patient_id = $patientId ORDER BY id DESC LIMIT 10");
$output .= "--- Consultations for Patient 13 ---\n";
while ($row = $res->fetch_assoc()) {
    $output .= json_encode($row) . "\n";
}

$res = $conn->query("SELECT * FROM appointments WHERE patient_id = $patientId ORDER BY id DESC LIMIT 10");
$output .= "\n--- Appointments for Patient 13 ---\n";
while ($row = $res->fetch_assoc()) {
    $output .= json_encode($row) . "\n";
}

file_put_contents('debug_output.txt', $output);
echo "Output saved to debug_output.txt\n";
?>
