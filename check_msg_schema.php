<?php
require_once 'db.php';
echo "=== Messages Table Schema ===\n";
$r = $conn->query('DESCRIBE messages');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
