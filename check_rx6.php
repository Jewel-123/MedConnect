<?php
include 'db.php';
$res = $conn->query("SELECT * FROM prescriptions_v2 WHERE id = 6");
print_r($res->fetch_assoc());

$res = $conn->query("SELECT * FROM prescription_orders WHERE prescription_id = 6");
print_r($res->fetch_assoc());
?>
