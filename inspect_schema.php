<?php
include 'db.php';
function describe($table, $conn) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    if ($res) {
        while($row = $res->fetch_assoc()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}
describe('prescriptions_v2', $conn);
describe('prescription_orders', $conn);
describe('pharmacy_inventory', $conn);
?>
