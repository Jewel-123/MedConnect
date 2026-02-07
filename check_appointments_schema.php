<?php
require_once 'db.php';
$res = $conn->query("DESCRIBE appointments");
if ($res) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} else {
    echo "Error describing table: " . $conn->error;
}