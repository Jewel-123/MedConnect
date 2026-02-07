<?php
require_once 'db.php';
$res = $conn->query("DESCRIBE doctor_profiles");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}