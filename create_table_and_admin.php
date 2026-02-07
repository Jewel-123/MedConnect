<?php
echo "=== CREATING USERS TABLE AND ADMIN ===\n\n";

$conn = new mysqli("localhost", "root", "", "medconnect");

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to medconnect database\n\n";

// Create users table
echo "Creating users table...\n";
$sql = "CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✅ Users table created successfully!\n\n";
} else {
    die("❌ Error creating table: " . $conn->error . "\n");
}

// Create admin user
echo "Creating admin user...\n";
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // admin123

$sql = "INSERT INTO users (name, email, password, role, status) 
        VALUES ('Admin User', 'admin@medconnect.com', '$hash', 'admin', 'approved')";

if ($conn->query($sql)) {
    echo "✅ Admin user created successfully!\n\n";
} else {
    if (strpos($conn->error, 'Duplicate') !== false) {
        echo "⚠️  Admin already exists\n\n";
    } else {
        die("❌ Error creating admin: " . $conn->error . "\n");
    }
}

// Verify
echo "=== VERIFICATION ===\n\n";
$result = $conn->query("SELECT * FROM users WHERE email = 'admin@medconnect.com'");
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "✅ ADMIN VERIFIED!\n\n";
    echo "ID: {$admin['id']}\n";
    echo "Name: {$admin['name']}\n";
    echo "Email: {$admin['email']}\n";
    echo "Role: {$admin['role']}\n";
    echo "Status: {$admin['status']}\n\n";
    
    echo "=== 🎉 SUCCESS! 🎉 ===\n\n";
    echo "You can now login at:\n";
    echo "http://localhost/medconnect/login.php\n\n";
    echo "Email: admin@medconnect.com\n";
    echo "Password: admin123\n\n";
    
    echo '<a href="login.php" style="display:inline-block;background:#10b981;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:18px;margin-top:20px;">GO TO LOGIN PAGE</a>';
} else {
    echo "❌ Verification failed\n";
}

$conn->close();