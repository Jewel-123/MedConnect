<?php
require_once 'db.php';
foreach(['consultations', 'appointments'] as $t) {
    echo "--- $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
    echo "\n";
}
?>
