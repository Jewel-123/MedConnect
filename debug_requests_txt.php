<?php
require_once 'db.php';

$output = "--- SCHEMA REPORT ---\n";

// Check Consultations Schema
$output .= "\n[CONSULTATIONS SCHEMA]\n";
$result = $conn->query("DESCRIBE consultations");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
    }
} else {
    $output .= "Error getting consultations schema: " . $conn->error . "\n";
}

// Check Appointments Schema
$output .= "\n[APPOINTMENTS SCHEMA]\n";
$result = $conn->query("DESCRIBE appointments");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
    }
} else {
    $output .= "Error getting appointments schema: " . $conn->error . "\n";
}

file_put_contents('debug_output.txt', $output);
echo "Schema report written to debug_output.txt\n";
$conn->close();