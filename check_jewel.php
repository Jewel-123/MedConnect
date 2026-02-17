<?php
require_once 'db.php';

$output = "";

$output .= "=== All Consultations for JEWEL BIJU ===\n";
$res = $conn->query("SELECT c.* FROM consultations c JOIN users u ON c.patient_id = u.id WHERE u.full_name = 'JEWEL BIJU' ORDER BY c.id DESC");
while ($row = $res->fetch_assoc()) {
    $output .= print_r($row, true);
}

$output .= "\n=== All Appointments for JEWEL BIJU ===\n";
$res = $conn->query("SELECT a.* FROM appointments a JOIN users u ON a.patient_id = u.id WHERE u.full_name = 'JEWEL BIJU' ORDER BY a.id DESC");
while ($row = $res->fetch_assoc()) {
    $output .= print_r($row, true);
}

file_put_contents('debug_jewel.txt', $output);
echo "Done.";
?>
