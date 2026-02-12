<?php
include 'db.php';
$res = $conn->query("SELECT email FROM users WHERE id = 4");
$row = $res->fetch_assoc();
echo "EMAIL:" . str_replace("@", "(at)", $row['email']) . "\n";
?>
