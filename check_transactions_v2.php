<?php
include 'db.php';
$res = $conn->query("SHOW CREATE TABLE payment_transactions");
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['Create Table'];
} else {
    echo "Error: " . $conn->error;
}
$conn->close();