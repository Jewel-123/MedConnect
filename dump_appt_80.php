<?php
require_once 'db.php';
$res = $conn->query("SELECT * FROM appointments WHERE id = 80");
$row = $res->fetch_assoc();
file_put_contents('appt_80_dump.txt', print_r($row, true));
?>
