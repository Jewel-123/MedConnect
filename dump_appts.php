<?php
require_once 'db.php';
$res = $conn->query("SELECT * FROM appointments ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
