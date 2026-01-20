@echo off
echo Creating medconnect database...

C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS medconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% equ 0 (
    echo Database created!
) else (
    echo Database already exists or error occurred
)

echo.
echo Creating users table...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, phone VARCHAR(20), role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL, status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', google_id VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"

echo.
echo Creating admin...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "INSERT IGNORE INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');"

echo.
echo Done! Check: http://localhost/medconnect/check_setup.php
start http://localhost/medconnect/check_setup.php
pause
