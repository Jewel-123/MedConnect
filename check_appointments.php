<?php
include 'db.php';
$res = $conn->query("DESCRIBE appointments");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$conn->close();