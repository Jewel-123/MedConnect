<?php
require_once 'db.php';

$output = "";
$output .= "=== Consultations from 2026-02-13 around 10:09 AM ===\n";
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE DATE(c.created_at) = '2026-02-13' AND (TIME(c.created_at) BETWEEN '10:05:00' AND '10:15:00')");
while ($row = $res->fetch_assoc()) {
    $output .= print_r($row, true);
}

$output .= "\n=== Appointments from 2026-02-13 around 10:09 AM ===\n";
$res = $conn->query("SELECT a.*, u.full_name as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE DATE(a.created_at) = '2026-02-13' AND (TIME(a.created_at) BETWEEN '10:05:00' AND '10:15:00')");
while ($row = $res->fetch_assoc()) {
    $output .= print_r($row, true);
}

file_put_contents('debug_1009.txt', $output);
echo "Done.";
?>
