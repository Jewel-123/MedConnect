<?php
require_once 'db.php';

$output = "";
foreach ([21, 43] as $patientId) {
    $res = $conn->query("SELECT * FROM consultations WHERE patient_id = $patientId ORDER BY id DESC LIMIT 5");
    $output .= "--- Consultations for Patient $patientId ---\n";
    while ($row = $res->fetch_assoc()) {
        $output .= json_encode($row) . "\n";
    }

    $res = $conn->query("SELECT * FROM appointments WHERE patient_id = $patientId ORDER BY id DESC LIMIT 5");
    $output .= "\n--- Appointments for Patient $patientId ---\n";
    while ($row = $res->fetch_assoc()) {
        $output .= json_encode($row) . "\n";
    }
}

file_put_contents('debug_output_v2.txt', $output);
echo "Output saved to debug_output_v2.txt\n";
?>
