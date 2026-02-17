<?php
require_once 'db.php';

$output = "";

function checkTable($conn, $table, &$output) {
    $output .= "--- Structure of '$table' ---\n";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $output .= $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        $output .= "Table '$table' does not exist or error: " . $conn->error . "\n";
    }
    $output .= "\n";
}

checkTable($conn, 'doctor_profiles', $output);
checkTable($conn, 'consultations', $output);
checkTable($conn, 'users', $output);

file_put_contents('schema_output.txt', $output);
echo "Schema written to schema_output.txt";
?>
