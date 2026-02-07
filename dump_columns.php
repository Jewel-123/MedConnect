<?php
require 'db.php';
$res = $conn->query("SHOW COLUMNS FROM consultations");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}