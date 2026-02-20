<?php
require_once 'db.php';
$tables = ['medical_records', 'reminders'];
$schema = [];
foreach ($tables as $table) {
    $res = $conn->query("DESCRIBE $table");
    $schema[$table] = $res->fetch_all(MYSQLI_ASSOC);
}
echo json_encode($schema, JSON_PRETTY_PRINT);
