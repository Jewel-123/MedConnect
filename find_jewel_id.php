<?php
require_once 'db.php';

$res = $conn->query("SELECT id, full_name, email FROM users WHERE full_name LIKE '%Jewel%'");
while ($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
?>
