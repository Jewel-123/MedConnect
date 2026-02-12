<?php
include 'db.php';
$res = $conn->query("SELECT id, email, full_name FROM users WHERE role = 'pharmacy'");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Email: [{$row['email']}] | Name: {$row['full_name']}\n";
}
?>
