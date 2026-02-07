<?php
require_once 'db.php';
$tables_res = $conn->query("SHOW TABLES");
while ($table_row = $tables_res->fetch_array()) {
    $table = $table_row[0];
    $fields_res = $conn->query("DESCRIBE $table");
    $has_updated_at = false;
    while ($field_row = $fields_res->fetch_assoc()) {
        if ($field_row['Field'] === 'updated_at') {
            $has_updated_at = true;
            break;
        }
    }
    echo "$table: " . ($has_updated_at ? "YES" : "NO") . "\n";
}
