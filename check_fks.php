<?php
require_once 'db.php';
$res = $conn->query("
    SELECT REFERENCED_TABLE_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = 'messages' 
      AND COLUMN_NAME = 'consultation_id'
      AND TABLE_SCHEMA = 'medconnect' 
      AND REFERENCED_TABLE_NAME IS NOT NULL
");
$row = $res->fetch_assoc();
echo "Consultation_id references: " . ($row['REFERENCED_TABLE_NAME'] ?? "NOTHING") . "\n";
?>
