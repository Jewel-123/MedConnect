<?php
require_once 'db.php';
$res = $conn->query("DESCRIBE consultations");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
