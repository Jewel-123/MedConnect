<?php
require_once 'db.php';

$output = "";

$output .= "=== Consultations from the last hour ===\n";
$one_hour_ago = date('Y-m-d H:i:s', time() - 3600);
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE c.created_at >= '$one_hour_ago' ORDER BY c.id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $output .= "ID: " . $row['id'] . "\n";
        $output .= "Patient: " . $row['patient_name'] . "\n";
        $output .= "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
        $output .= "Status: " . $row['status'] . "\n";
        $output .= "Payment Status: " . $row['payment_status'] . "\n";
        $output .= "Created At: " . $row['created_at'] . "\n";
        $output .= "Symptoms: " . $row['symptoms'] . "\n";
        $output .= "Matched Specialty: " . ($row['matched_specialty'] ?? 'NULL') . "\n";
        $output .= "-------------------\n";
    }
} else {
    $output .= "Error: " . $conn->error . "\n";
}

$output .= "\n=== Appointments from the last hour ===\n";
$res = $conn->query("SELECT a.*, u.full_name as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.created_at >= '$one_hour_ago' ORDER BY a.id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $output .= "ID: " . $row['id'] . "\n";
        $output .= "Patient: " . $row['patient_name'] . "\n";
        $output .= "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
        $output .= "Status: " . $row['status'] . "\n";
        $output .= "Payment Status: " . $row['payment_status'] . "\n";
        $output .= "Created At: " . $row['created_at'] . "\n";
        $output .= "Reason: " . ($row['reason'] ?? 'EMPTY') . "\n";
        $output .= "Check-in Status: " . ($row['check_in_status'] ?? 'N/A') . "\n";
        $output .= "-------------------\n";
    }
}

file_put_contents('debug_output.txt', $output);
echo "Done. See debug_output.txt\n";
?>
