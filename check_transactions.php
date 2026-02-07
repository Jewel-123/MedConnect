<?php
include 'db.php';
$res = $conn->query("DESCRIBE payment_transactions");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$conn->close();