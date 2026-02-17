<?php
require_once 'db.php';
header('Content-Type: text/plain');
$res = $conn->query("SHOW COLUMNS FROM prescription_orders");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
