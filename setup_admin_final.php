<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ADMIN SETUP ===\n\n";

$conn = new mysqli("localhost", "root", "");

echo "1. Dropping database...\n";
if ($conn->query("DROP DATABASE IF EXISTS medconnect")) {
    echo "   ✓ Dropped\n";
}

echo "2. Creating database...\n";
if ($conn->query("CREATE DATABASE medconnect")) {
    echo "   ✓ Created\n";
}

echo "3. Using database...\n";
$conn->select_db("medconnect");

echo "4. Creating users table...\n";
$conn->query("CREATE TABLE users (
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
)");
echo "   ✓ Table created\n";

echo "5. Creating admin...\n";
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$conn->query("INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$hash', 'admin', 'approved')");
echo "   ✓ Admin created\n";

echo "\n✓✓✓ SUCCESS! ✓✓✓\n\n";
echo "Login: http://localhost/medconnect/login.php\n";
echo "Email: admin@medconnect.com\n";
echo "Password: admin123\n";
?>
