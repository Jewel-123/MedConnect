<?php
require_once 'db.php';

// Generate correct password hash
$password = 'pharmacy123';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Generated hash: " . $hash . "\n\n";

// Update the password in database
$email = 'pharmacy@medconnect.com';

$stmt = $conn->prepare("UPDATE users SET password = ?, status = 'approved' WHERE email = ?");
$stmt->bind_param("ss", $hash, $email);

if ($stmt->execute()) {
    echo "✅ Password updated successfully!\n\n";
    
    // Verify it works
    $checkStmt = $conn->prepare("SELECT id, email, password, role, status FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "Account details:\n";
        echo "ID: " . $row['id'] . "\n";
        echo "Email: " . $row['email'] . "\n";
        echo "Role: " . $row['role'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        
        // Test password verification
        if (password_verify($password, $row['password'])) {
            echo "\n✅ Password verification: SUCCESS\n";
            echo "\nYou can now login with:\n";
            echo "Email: pharmacy@medconnect.com\n";
            echo "Password: pharmacy123\n";
        } else {
            echo "\n❌ Password verification: FAILED\n";
        }
    }
} else {
    echo "❌ Failed to update password: " . $conn->error . "\n";
}
?>
