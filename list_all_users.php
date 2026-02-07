<?php
require_once 'db.php';

$output = "ID | Name | Email | Role | Status\n";
$output .= "------------------------------------------------\n";
$result = $conn->query("SELECT id, full_name, email, role, status FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $output .= "{$row['id']} | {$row['full_name']} | {$row['email']} | {$row['role']} | {$row['status']}\n";
    }
} else {
    $output .= "Error: " . $conn->error;
}

file_put_contents('users_list_direct.txt', $output);
echo "Written to users_list_direct.txt";