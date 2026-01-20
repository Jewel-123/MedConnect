<?php
require_once 'db.php';

echo "=== ROBUST DATABASE ALIGNMENT ===\n\n";

function addColumnIfNotExists($conn, $table, $column, $definition, $after = '') {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($after) $sql .= " AFTER `$after`";
        if ($conn->query($sql)) {
            echo "✓ Added column $column to $table\n";
        } else {
            echo "✗ Error adding column $column: " . $conn->error . "\n";
        }
    } else {
        echo "i Column $column already exists in $table\n";
    }
}

// 1. Rename 'name' to 'full_name' if 'name' exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'name'");
if ($result && $result->num_rows > 0) {
    echo "Renaming 'name' to 'full_name'...\n";
    if ($conn->query("ALTER TABLE users CHANGE COLUMN name full_name VARCHAR(100) NOT NULL")) {
        echo "✓ Renamed successfully\n";
    } else {
        echo "✗ Error renaming: " . $conn->error . "\n";
    }
}

// 2. Add columns
addColumnIfNotExists($conn, 'users', 'is_verified', 'TINYINT(1) DEFAULT 0', 'status');
addColumnIfNotExists($conn, 'users', 'google_id', 'VARCHAR(255) DEFAULT NULL', 'is_verified');

// 3. Update role and status ENUMs
echo "Updating ENUMs...\n";
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic', 'hospital') NOT NULL");
$conn->query("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'pending_onboarding') DEFAULT 'pending'");

// 4. Update the admin user
echo "Verifying admin user...\n";
$email = 'admin@medconnect.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($check && $check->num_rows > 0) {
    $conn->query("UPDATE users SET status = 'approved', is_verified = 1, password = '$hash' WHERE email = '$email'");
    echo "✓ Admin user updated and password reset\n";
} else {
    $conn->query("INSERT INTO users (full_name, email, password, role, status, is_verified) VALUES ('Admin User', '$email', '$hash', 'admin', 'approved', 1)");
    echo "✓ Admin user created\n";
}

echo "\n=== ALIGNMENT COMPLETE ===\n";
?>
