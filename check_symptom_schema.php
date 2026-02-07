<?php
require_once 'db.php';
$tables = ['symptom_checks', 'symptom_keywords', 'consultations'];
foreach ($tables as $t) {
    echo "Table: $t\n";
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while($row = $res->fetch_assoc()) echo "- " . $row['Field'] . "\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}