<?php
require_once 'db.php';

echo "=== Consultations from the last hour ===\n";
$one_hour_ago = date('Y-m-d H:i:s', time() - 3600);
$res = $conn->query("SELECT c.*, u.full_name as patient_name FROM consultations c JOIN users u ON c.patient_id = u.id WHERE c.created_at >= '$one_hour_ago' ORDER BY c.id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Patient: " . $row['patient_name'] . "\n";
        echo "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Payment Status: " . $row['payment_status'] . "\n";
        echo "Created At: " . $row['created_at'] . "\n";
        echo "Symptoms: " . $row['symptoms'] . "\n";
        echo "Matched Specialty: " . ($row['matched_specialty'] ?? 'NULL') . "\n";
        echo "-------------------\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n=== Appointments from the last hour ===\n";
$res = $conn->query("SELECT a.*, u.full_name as patient_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE a.created_at >= '$one_hour_ago' ORDER BY a.id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Patient: " . $row['patient_name'] . "\n";
        echo "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Payment Status: " . $row['payment_status'] . "\n";
        echo "Created At: " . $row['created_at'] . "\n";
        echo "Reason: " . $row['reason'] . "\n";
        echo "-------------------\n";
    }
}
?>
