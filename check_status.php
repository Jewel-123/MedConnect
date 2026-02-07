<?php
require_once 'db.php';

// Check the test consultation status
$res = $conn->query("SELECT id, patient_id, doctor_id, status, matched_specialty, created_at FROM consultations WHERE id = 46");
echo "TEST CONSULTATION (ID 46):\n";
if ($res && $row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Patient ID: " . $row['patient_id'] . "\n";
    echo "Doctor ID: " . ($row['doctor_id'] ?? 'NULL') . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Specialty: " . ($row['matched_specialty'] ?? 'NULL') . "\n";
    echo "Created: " . $row['created_at'] . "\n\n";
} else {
    echo "Not found or error: " . $conn->error . "\n\n";
}

// Check all pending consultations
$res = $conn->query("SELECT id, patient_id, doctor_id, status FROM consultations WHERE status = 'pending' AND doctor_id IS NULL");
echo "ALL UNASSIGNED PENDING:\n";
if ($res) {
    $count = 0;
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Patient: " . $row['patient_id'] . ", Status: " . $row['status'] . "\n";
        $count++;
    }
    echo "Total: $count\n\n";
} else {
    echo "Error: " . $conn->error . "\n\n";
}

// Check doctor info
$res = $conn->query("SELECT u.id, u.full_name, u.email, dp.specialty FROM users u LEFT JOIN doctor_profiles dp ON u.id = dp.user_id WHERE u.role = 'doctor' LIMIT 5");
echo "DOCTORS IN SYSTEM:\n";
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Name: " . $row['full_name'] . ", Specialty: " . ($row['specialty'] ?? 'NULL') . "\n";
    }
}