<?php
include 'db.php';

function dump($title, $res) {
    echo "--- $title ---\n";
    if (!$res) {
        echo "Query failed\n";
        return;
    }
    if ($res->num_rows == 0) {
        echo "No results\n";
        return;
    }
    while ($row = $res->fetch_assoc()) {
        foreach ($row as $k => $v) echo "$k: [$v]\n";
        echo "----------------\n";
    }
}

dump("prescriptions_v2 (ID 6)", $conn->query("SELECT * FROM prescriptions_v2 WHERE id = 6"));
dump("prescription_orders (RX ID 6)", $conn->query("SELECT * FROM prescription_orders WHERE prescription_id = 6"));
dump("User 21 (Patient)", $conn->query("SELECT * FROM users WHERE id = 21"));
dump("User 4 (Pharmacy)", $conn->query("SELECT * FROM users WHERE id = 4"));
?>
