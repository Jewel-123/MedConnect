<?php
require_once 'db.php';

$userId = 14;
$newEmail = 'smith@gmail.com';
$newPassword = '123456';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
$stmt->bind_param("ssi", $newEmail, $hashedPassword, $userId);

if ($stmt->execute()) {
    echo "Success: Updated User ID $userId with email '$newEmail' and new password.";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
?>
