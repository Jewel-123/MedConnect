<?php
require_once 'db.php';
$table = 'medicines';
echo "[$table columns]\n";
$res = $conn->query("DESCRIBE $table");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>
