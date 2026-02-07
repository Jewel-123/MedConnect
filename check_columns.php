<?php
require_once 'db.php';
$tables = ['doctor_profiles', 'consultations'];
foreach ($tables as $table) {
    echo "Columns for $table:\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}