<?php
require_once 'db.php';
$tables = ['consultations', 'symptom_checks', 'red_flag_symptoms', 'symptom_keywords'];
foreach ($tables as $table) {
    echo "[$table columns]\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo "- " . $row['Field'] . "\n";
        }
    } else {
        echo "! Table $table not found.\n";
    }
    echo "\n";
}