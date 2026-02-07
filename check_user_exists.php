<?php
require_once 'db.php';

$email = 'smith@gmail.com';
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "User found: " . print_r($result->fetch_assoc(), true);
} else {
    echo "User NOT found";
}