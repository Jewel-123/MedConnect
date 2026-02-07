<?php
require_once 'db.php';

$tables = ['payment_transactions', 'consultations', 'prescription_orders', 'appointments'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . "\n";
        }
    } else {
        echo "Error describing $table: " . $conn->error . "\n";
    }
    echo "\n";
}