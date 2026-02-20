<?php
require_once 'db.php';
function describe($conn, $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s\n", $row['Field'], $row['Type'], $row['Null'], $row['Key']);
    }
    echo "\n";
}
describe($conn, 'appointments');
describe($conn, 'consultations');
describe($conn, 'consultation_sessions');
describe($conn, 'messages');
?>
