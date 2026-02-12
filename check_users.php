<?php
include 'db.php';
$res = $conn->query("SELECT id, full_name, email, role FROM users WHERE id IN (4, 21)");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['full_name']} | Email: {$row['email']} | Role: {$row['role']}\n";
}
?>
