<?php
require_once 'db.php';
$res = $conn->query("SHOW TABLES");
$tables = [];
while($row = $res->fetch_array()) {
    $tables[] = $row[0];
}
file_put_contents('current_tables_list.txt', implode("\n", $tables));
echo "Tables listed successfully in current_tables_list.txt\n";