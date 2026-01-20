@echo off
echo ========================================
echo MANUAL FIX FOR CORRUPTED DATABASE
echo ========================================
echo.
echo Follow these steps:
echo.
echo 1. Open XAMPP Control Panel
echo 2. Click STOP next to MySQL
echo 3. Wait for MySQL to fully stop
echo 4. Open File Explorer and go to:
echo    C:\xampp\mysql\data\medconnect
echo 5. DELETE the entire 'medconnect' folder
echo 6. Go back to XAMPP and click START next to MySQL
echo 7. Run this script again
echo.
echo ========================================
echo.
echo Press any key if you've done steps 1-6...
pause

echo.
echo Creating fresh database...
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS medconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo Creating users table...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, phone VARCHAR(20), role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL, status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', google_id VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"

echo Creating admin user...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');"

echo.
echo ========================================
echo DONE!
echo ========================================
echo.
echo Login at: http://localhost/medconnect/login.php
echo Email: admin@medconnect.com
echo Password: admin123
echo.
start http://localhost/medconnect/login.php
pause
