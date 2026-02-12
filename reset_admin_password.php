<?php
require_once 'db.php';

$email = 'admin@medconnect.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hash, $email);

if ($stmt->execute()) {
    echo "<h1>Password Updated!</h1>";
    echo "<p>Admin password has been reset to: <strong>admin123</strong></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
} else {
    echo "Error: " . $conn->error;
}
?>
