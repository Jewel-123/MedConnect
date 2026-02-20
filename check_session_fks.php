<?php
require_once 'db.php';
$res = $conn->query("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'consultation_sessions' AND TABLE_SCHEMA = 'medconnect' AND REFERENCED_TABLE_NAME IS NOT NULL
");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
