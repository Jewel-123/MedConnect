<?php
require_once 'db.php';

$output = "";
$output .= "=== Appointment ID 38 ===\n";
$res = $conn->query("SELECT a.*, u.full_name as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.id = 38");
$output .= print_r($res->fetch_assoc(), true);

$output .= "\n=== Consultation ID 69 ===\n";
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE c.id = 69");
$output .= print_r($res->fetch_assoc(), true);

file_put_contents('debug_final_check.txt', $output);
echo "Done.";
?>
