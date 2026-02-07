<?php
include 'db.php';
function check_table($conn, $table) {
    echo "--- $table ---\n";
    $res = $conn->query("SHOW TABLES LIKE '$table'");
    if ($res->num_rows == 0) {
        echo "Table $table missing\n";
        return;
    }
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        printf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
    }
}

check_table($conn, 'appointments');
check_table($conn, 'prescription_orders');
$conn->close();