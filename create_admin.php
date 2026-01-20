<?php
/**
 * Create/Reset Admin Account
 * Run this script once to create or reset the admin account
 */

require_once 'db.php';

// Delete existing admin account if it exists
$conn->query("DELETE FROM users WHERE email = 'admin@medconnect.com'");

// Create new admin account
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
$fullName = 'System Admin';
$email = 'admin@medconnect.com';
$role = 'admin';
$status = 'approved';

$stmt->bind_param("sssss", $fullName, $email, $adminPassword, $role, $status);

if ($stmt->execute()) {
    echo "<h2 style='color: green;'>✅ Admin Account Created Successfully!</h2>";
    echo "<p><strong>Email:</strong> admin@medconnect.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<br>";
    echo "<p><a href='login.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
} else {
    echo "<h2 style='color: red;'>❌ Error creating admin account</h2>";
    echo "<p>" . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
