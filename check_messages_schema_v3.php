<?php
require_once 'db.php';
$r = $conn->query('DESCRIBE messages');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
