<?php
include 'db.php';
$res = $conn->query("SELECT email FROM users WHERE id = 4");
$row = $res->fetch_assoc();
file_put_contents('pharmacy_email.txt', $row['email']);
echo "Email saved to pharmacy_email.txt\n";
?>
