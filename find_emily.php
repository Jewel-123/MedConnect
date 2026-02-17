<?php
require_once 'db.php';

$res = $conn->query("SELECT id, full_name, role FROM users WHERE full_name LIKE '%Emily Smith%'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
