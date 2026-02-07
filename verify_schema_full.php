<?php
require_once 'db.php';

$tables = ['consultations', 'appointments', 'doctor_earnings', 'payment_transactions', 'users', 'doctor_profiles'];
$output = "=== DATABASE SCHEMA VERIFICATION ===\n\n";

foreach ($tables as $table) {
    $output .= "--- Table: $table ---\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($col = $result->fetch_assoc()) {
            $output .= sprintf("%-25s %-20s %-10s %s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Default'] ?? 'NULL'
            );
        }
    } else {
        $output .= "Table not found or error: " . $conn->error . "\n";
    }
    $output .= "\n";
}

file_put_contents('schema_debug.txt', $output);
echo "Schema debug written to schema_debug.txt";