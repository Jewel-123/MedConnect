<?php
require_once 'db.php';
$tables = ['consultations', 'red_flag_symptoms', 'symptom_keywords', 'symptom_checks'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Table not found.\n";
    }
    echo "\n";
}