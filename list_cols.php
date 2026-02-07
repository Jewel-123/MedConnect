<?php
require_once 'db.php';
$res = $conn->query("DESC consultations");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}