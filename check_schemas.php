<?php
include 'db.php';
function describeTable($conn, $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIB $table"); // Fixed typo DESCRIBE
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        printf("%-20s | %-20s | %s\n", $row['Field'], $row['Type'], $row['Null']);
    }
}
describeTable($conn, 'prescriptions_v2');
describeTable($conn, 'prescription_orders');
?>