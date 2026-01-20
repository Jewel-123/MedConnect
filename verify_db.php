<?php
// Database verification and fix script
include 'db.php';

echo "<h2>MedConnect Database Verification & Fix</h2>";
echo "<pre>";

// 1. Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "❌ ERROR: 'users' table does not exist!\n";
    echo "Please run setup_db.php first.\n";
    exit;
}
echo "✅ Users table exists\n\n";

// 2. Check current structure of users table
echo "Current structure of 'users' table:\n";
$columns = $conn->query("DESCRIBE users");
$existingColumns = [];
while ($col = $columns->fetch_assoc()) {
    $existingColumns[] = $col['Field'];
    echo "  - {$col['Field']} ({$col['Type']})\n";
}
echo "\n";

// 3. Add missing columns if needed
$columnsToAdd = [];

if (!in_array('google_id', $existingColumns)) {
    $columnsToAdd[] = "ADD COLUMN google_id VARCHAR(255) DEFAULT NULL AFTER role";
}

if (count($columnsToAdd) > 0) {
    echo "Adding missing columns...\n";
    foreach ($columnsToAdd as $columnSQL) {
        $sql = "ALTER TABLE users $columnSQL";
        if ($conn->query($sql)) {
            echo "✅ Added column successfully\n";
        } else {
            echo "❌ Error: " . $conn->error . "\n";
        }
    }
    echo "\n";
} else {
    echo "✅ All required columns exist\n\n";
}

// 4. Ensure password_resets table exists
$result = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($result->num_rows == 0) {
    echo "Creating password_resets table...\n";
    $sql = "CREATE TABLE `password_resets` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(100) NOT NULL,
        `token` VARCHAR(255) NOT NULL,
        `expires_at` DATETIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql)) {
        echo "✅ password_resets table created\n\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n\n";
    }
} else {
    echo "✅ password_resets table exists\n\n";
}

// 5. Check if there are any users
$userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
echo "Current users in database: $userCount\n\n";

if ($userCount == 0) {
    echo "⚠️  No users found. Creating demo user...\n";
    $demoEmail = 'demo@medconnect.com';
    $demoPassword = password_hash('password123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $name = 'Demo User';
    $role = 'patient';
    $stmt->bind_param("ssss", $name, $demoEmail, $demoPassword, $role);
    
    if ($stmt->execute()) {
        echo "✅ Demo user created\n";
        echo "   Email: demo@medconnect.com\n";
        echo "   Password: password123\n\n";
    }
}

// 6. Display current users
echo "Current users:\n";
$users = $conn->query("SELECT id, full_name, email, role FROM users");
if ($users->num_rows > 0) {
    echo "ID | Name | Email | Role\n";
    echo "---|------|-------|-----\n";
    while ($user = $users->fetch_assoc()) {
        echo "{$user['id']} | {$user['full_name']} | {$user['email']} | {$user['role']}\n";
    }
} else {
    echo "No users found\n";
}

echo "\n";
echo "========================================\n";
echo "Database verification complete!\n";
echo "You can now close this page.\n";
echo "</pre>";

$conn->close();
?>
