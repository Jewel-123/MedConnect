<?php
require_once 'db.php';
$tables = ['prescription_orders', 'prescriptions_v2'];
foreach ($tables as $table) {
    echo "[$table columns]\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo $row['Field'] . "\n";
        }
    } else {
        echo "Error describing $table: " . $conn->error . "\n";
    }
    echo "\n";
}
?>