<?php
/**
 * Debug Admin Account
 * This script checks if the admin account exists and shows its details
 */

require_once 'db.php';

echo "<h2>Admin Account Debug Information</h2>";
echo "<hr>";

// Check if admin account exists
$result = $conn->query("SELECT * FROM users WHERE email = 'admin@medconnect.com'");

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<h3 style='color: green;'>✅ Admin Account EXISTS</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>" . $admin['id'] . "</td></tr>";
    echo "<tr><td>Full Name</td><td>" . $admin['full_name'] . "</td></tr>";
    echo "<tr><td>Email</td><td>" . $admin['email'] . "</td></tr>";
    echo "<tr><td>Role</td><td>" . $admin['role'] . "</td></tr>";
    echo "<tr><td>Status</td><td>" . $admin['status'] . "</td></tr>";
    echo "<tr><td>Password Hash</td><td>" . substr($admin['password'], 0, 50) . "...</td></tr>";
    echo "</table>";
    
    echo "<br><h3>Password Verification Test</h3>";
    $testPassword = 'admin123';
    if (password_verify($testPassword, $admin['password'])) {
        echo "<p style='color: green; font-weight: bold;'>✅ Password 'admin123' MATCHES the stored hash!</p>";
        echo "<p style='color: orange;'>⚠️ If login still fails, the issue is in the auth.php logic.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Password 'admin123' DOES NOT MATCH the stored hash!</p>";
        echo "<p>The password hash in the database is incorrect. Click the button below to fix it:</p>";
        echo "<form method='post'>";
        echo "<button type='submit' name='fix_password' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Fix Admin Password</button>";
        echo "</form>";
    }
} else {
    echo "<h3 style='color: red;'>❌ Admin Account DOES NOT EXIST</h3>";
    echo "<p>The admin account needs to be created. Click the button below:</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='create_admin' style='background: #2196F3; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Create Admin Account</button>";
    echo "</form>";
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_admin'])) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $fullName = 'System Admin';
        $email = 'admin@medconnect.com';
        $role = 'admin';
        $status = 'approved';
        $stmt->bind_param("sssss", $fullName, $email, $adminPassword, $role, $status);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Admin account created! <a href='check_admin.php'>Refresh page</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
    
    if (isset($_POST['fix_password'])) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@medconnect.com'");
        $stmt->bind_param("s", $adminPassword);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Password fixed! <a href='check_admin.php'>Refresh page</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
}

echo "<br><hr>";
echo "<p><a href='login.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

$conn->close();