<?php
require_once 'db.php';

function dump_table($conn, $table) {
    echo "\n--- $table schema ---\n";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "$table table NOT found.\n";
    }
}

echo "--- Tables ---\n";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
    echo $row[0] . "\n";
}

dump_table($conn, 'users');
dump_table($conn, 'consultations');
dump_table($conn, 'appointments');
dump_table($conn, 'consultation_payments');

echo "\n--- Searching for Emily Smith ---\n";
$result = $conn->query("SELECT id, name, role FROM users WHERE name LIKE '%Emily%'");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo "\n--- Searching for recent consultations/appointments with fever ---\n";
if (in_array('consultations', $tables)) {
    $result = $conn->query("SELECT * FROM consultations WHERE symptoms LIKE '%fever%' OR reason LIKE '%fever%' ORDER BY id DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "Consultation ID: " . $row['id'] . " | Patient: " . $row['patient_id'] . " | Doctor: " . $row['doctor_id'] . " | Status: " . $row['status'] . " | Payment: " . ($row['payment_status'] ?? 'N/A') . "\n";
    }
}
if (in_array('appointments', $tables)) {
    $result = $conn->query("SELECT * FROM appointments WHERE reason LIKE '%fever%' ORDER BY id DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "Appointment ID: " . $row['id'] . " | Patient: " . $row['patient_id'] . " | Doctor: " . $row['doctor_id'] . " | Status: " . $row['status'] . " | Payment: " . ($row['payment_status'] ?? 'N/A') . "\n";
    }
}
?>
