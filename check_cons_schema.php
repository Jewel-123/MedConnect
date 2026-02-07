<?php
require_once 'db.php';
$r = $conn->query('DESCRIBE consultations');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
