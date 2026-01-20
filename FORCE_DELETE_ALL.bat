@echo off
echo ========================================
echo FORCE DELETE ALL MEDCONNECT DATA
echo ========================================
echo.
echo This will:
echo 1. Stop MySQL
echo 2. Delete all medconnect database files
echo 3. Restart MySQL
echo 4. Create fresh database with admin
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo Step 1: Stopping MySQL...
net stop mysql 2>nul
taskkill /F /IM mysqld.exe 2>nul
timeout /t 2 /nobreak >nul

echo Step 2: Deleting database files...
if exist "C:\xampp\mysql\data\medconnect" (
    echo Deleting medconnect folder...
    rd /s /q "C:\xampp\mysql\data\medconnect"
    echo Done!
) else (
    echo Folder doesn't exist
)

if exist "C:\xampp\mysql\data\medconnectnew" (
    echo Deleting medconnectnew folder...
    rd /s /q "C:\xampp\mysql\data\medconnectnew"
    echo Done!
)

if exist "C:\xampp\mysql\data\medconnect_new" (
    echo Deleting medconnect_new folder...
    rd /s /q "C:\xampp\mysql\data\medconnect_new"
    echo Done!
)

echo.
echo Step 3: Starting MySQL...
net start mysql
timeout /t 3 /nobreak >nul

echo.
echo Step 4: Creating fresh database...
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE medconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo Database created!

echo.
echo Step 5: Creating users table...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, phone VARCHAR(20), role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL, status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', google_id VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
echo Table created!

echo.
echo Step 6: Creating admin user...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');"
echo Admin created!

echo.
echo ========================================
echo SUCCESS! Everything is clean and fresh!
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
