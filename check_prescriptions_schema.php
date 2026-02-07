<?php
include 'db.php';
$res = $conn->query("DESCRIBE prescriptions_v2");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$conn->close();