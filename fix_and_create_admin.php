<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== FIXING DATABASE ISSUE ===\n\n";

$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Cannot connect to MySQL. Is it running in XAMPP?\n");
}

echo "Connected to MySQL successfully!\n\n";

// Get list of all databases
echo "Current databases:\n";
$result = $conn->query("SHOW DATABASES");
while ($row = $result->fetch_array()) {
    echo "  - {$row[0]}\n";
}

echo "\n--- Attempting to use medconnect database ---\n";

// Try to use medconnect
$conn->select_db("medconnect");

echo "Checking if users table exists...\n";
$result = $conn->query("SHOW TABLES LIKE 'users'");

if ($result && $result->num_rows > 0) {
    echo "✓ Users table exists!\n";
    
    // Check if admin exists
    $adminCheck = $conn->query("SELECT * FROM users WHERE email = 'admin@medconnect.com'");
    if ($adminCheck && $adminCheck->num_rows > 0) {
        echo "✓ Admin already exists!\n";
        $admin = $adminCheck->fetch_assoc();
        echo "   ID: {$admin['id']}\n";
        echo "   Name: {$admin['name']}\n";
        echo "   Email: {$admin['email']}\n";
        echo "   Role: {$admin['role']}\n";
        echo "   Status: {$admin['status']}\n";
    } else {
        echo "✗ Admin doesn't exist, creating...\n";
        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if ($conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$hash', 'admin', 'approved')")) {
            echo "✓ Admin created!\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
        }
    }
} else {
    echo "✗ Users table doesn't exist, creating...\n";
    
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        google_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "✓ Users table created!\n";
        
        echo "Creating admin...\n";
        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if ($conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$hash', 'admin', 'approved')")) {
            echo "✓ Admin created!\n";
        } else {
            echo "✗ Error: " . $conn->error . "\n";
        }
    } else {
        echo "✗ Error creating table: " . $conn->error . "\n";
    }
}

echo "\n=== DONE ===\n\n";
echo "Try logging in now:\n";
echo "URL: http://localhost/medconnect/login.php\n";
echo "Email: admin@medconnect.com\n";
echo "Password: admin123\n";

$conn->close();