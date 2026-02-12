<?php
// Test database connection and user table
require_once 'db.php';

echo "=== Database Connection Test ===\n\n";

// Test connection
if ($conn->connect_error) {
    echo "❌ Connection FAILED: " . $conn->connect_error . "\n";
    exit;
} else {
    echo "✅ Database connected successfully\n\n";
}

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ Users table exists\n\n";
    
    // Count users
    $count = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $count->fetch_assoc()['total'];
    echo "📊 Total users in database: $total\n\n";
    
    // List all users
    echo "=== All Users ===\n";
    $users = $conn->query("SELECT id, full_name, email, role, status, is_verified FROM users");
    if ($users->num_rows > 0) {
        while ($user = $users->fetch_assoc()) {
            echo sprintf(
                "ID: %d | Name: %s | Email: %s | Role: %s | Status: %s | Verified: %s\n",
                $user['id'],
                $user['full_name'],
                $user['email'],
                $user['role'] ?? 'NULL',
                $user['status'] ?? 'NULL',
                $user['is_verified'] ? 'Yes' : 'No'
            );
        }
    } else {
        echo "⚠️  No users found in database\n";
    }
} else {
    echo "❌ Users table does NOT exist\n";
    echo "⚠️  Database needs to be initialized\n";
}

$conn->close();
?>
