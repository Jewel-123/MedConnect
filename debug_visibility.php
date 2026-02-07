<?php
require_once 'db.php';
header('Content-Type: text/plain');

echo "Table Schema (consultations):\n";
$res = $conn->query("DESCRIBE consultations");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . " - Default: " . ($row['Default'] ?: 'None') . "\n";
}
echo "-------------------\n\n";

echo "Recent Consultations:\n";
$res = $conn->query("SELECT id, patient_id, doctor_id, status, matched_specialty, symptoms, created_at FROM consultations ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Patient: " . $row['patient_id'] . "\n";
    echo "Doctor: " . ($row['doctor_id'] ?: 'NULL') . "\n";
    echo "Status: '" . $row['status'] . "'\n";
    echo "Specialty: " . $row['matched_specialty'] . "\n";
    echo "Symptoms: " . substr($row['symptoms'], 0, 100) . "...\n";
    echo "Created: " . $row['created_at'] . "\n";
    echo "-------------------\n";
}

echo "\nDoctor Sessions Check:\n";
session_start();
echo "Stored Session Doctor ID: " . ($_SESSION['user_id'] ?? 'Not Logged In') . "\n";
echo "Stored Session Role: " . ($_SESSION['role'] ?? 'N/A') . "\n";

// Check Sophia Martinez ID
$sophia = $conn->query("SELECT id FROM users WHERE full_name LIKE '%Sophia Martinez%'")->fetch_assoc();
echo "Sophia Martinez User ID: " . ($sophia['id'] ?? 'Not found') . "\n";