@echo off
echo ========================================
echo DELETE ALL MEDCONNECT DATABASES
echo ========================================
echo.
echo WARNING: This will delete:
echo - medconnect
echo - medconnectnew
echo - medconnect_new
echo - Any other medconnect databases
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Deleting all medconnect databases...
echo.

C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS medconnect;"
echo Dropped: medconnect

C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS medconnectnew;"
echo Dropped: medconnectnew

C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS medconnect_new;"
echo Dropped: medconnect_new

echo.
echo ========================================
echo ALL DATABASES DELETED
echo ========================================
echo.
echo Now creating fresh database with admin...
echo.

C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE medconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo Created: medconnect

C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, phone VARCHAR(20), role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL, status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', google_id VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
echo Created: users table

C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');"
echo Created: Admin user

echo.
echo ========================================
echo SUCCESS! Fresh database created
echo ========================================
echo.
echo Login credentials:
echo URL: http://localhost/medconnect/login.php
echo Email: admin@medconnect.com
echo Password: admin123
echo.
echo Opening login page...
start http://localhost/medconnect/login.php
echo.
pause
