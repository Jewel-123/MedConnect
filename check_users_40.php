<?php
require_once 'db.php';
$ids = [21, 25];
echo "=== Users Check ===\n";
foreach ($ids as $id) {
    $res = $conn->query("SELECT id, full_name, role FROM users WHERE id = $id");
    print_r($res->fetch_assoc());
}
