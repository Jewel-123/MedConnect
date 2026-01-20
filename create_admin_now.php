<?php
echo "=== SETTING UP ADMIN DASHBOARD ===\n\n";

// Connect
$conn = new mysqli("localhost", "root", "", "medconnect");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "Step 1: Creating admin user...\n";

// Create admin user with password: admin123
$email = 'admin@medconnect.com';
$name = 'Admin User';
$password = password_hash('admin123', PASSWORD_DEFAULT);
$role = 'admin';

// Check if admin exists
$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check && $check->num_rows > 0) {
    echo "  Admin already exists, updating...\n";
    $conn->query("UPDATE users SET password = '$password', status = 'approved', role = 'admin' WHERE email = '$email'");
} else {
    echo "  Creating new admin...\n";
    $sql = "INSERT INTO users (name, email, password, role, status, created_at) 
            VALUES ('$name', '$email', '$password', '$role', 'approved', NOW())";
    if ($conn->query($sql)) {
        echo "  ✓ Admin created successfully!\n";
    } else {
        echo "  ✗ Error: " . $conn->error . "\n";
    }
}

echo "\n=== SETUP COMPLETE ===\n\n";
echo "Admin Dashboard Login:\n";
echo "  URL: http://localhost/medconnect/admin_dashboard.php\n";
echo "  Email: admin@medconnect.com\n";
echo "  Password: admin123\n\n";

echo "Or login at: http://localhost/medconnect/login.php\n";

$conn->close();
?>
