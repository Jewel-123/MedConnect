@echo off
echo ========================================
echo CLEANING CORRUPTED DATABASE FILES
echo ========================================
echo.
echo This will delete the corrupted medconnect database files
echo and create a fresh database.
echo.
pause

echo.
echo Step 1: Stopping MySQL...
C:\xampp\mysql\bin\mysqladmin.exe -u root shutdown 2>nul

timeout /t 3 /nobreak >nul

echo Step 2: Deleting corrupted database folder...
if exist "C:\xampp\mysql\data\medconnect" (
    rmdir /s /q "C:\xampp\mysql\data\medconnect"
    echo    Deleted corrupted folder
) else (
    echo    Folder doesn't exist
)

echo Step 3: Starting MySQL...
net start mysql 2>nul
if %errorlevel% neq 0 (
    echo    Please start MySQL manually from XAMPP Control Panel
    pause
)

timeout /t 3 /nobreak >nul

echo Step 4: Creating fresh database...
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE medconnect;"

echo Step 5: Creating users table and admin...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, phone VARCHAR(20), role ENUM('patient', 'doctor', 'admin', 'pharmacy', 'clinic') NOT NULL, status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', google_id VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);"

echo Step 6: Creating admin user...
C:\xampp\mysql\bin\mysql.exe -u root medconnect -e "INSERT INTO users (name, email, password, role, status) VALUES ('Admin User', 'admin@medconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');"

echo.
echo ========================================
echo DONE! Database cleaned and admin created
echo ========================================
echo.
echo Login at: http://localhost/medconnect/login.php
echo Email: admin@medconnect.com
echo Password: admin123
echo.
echo Opening login page...
start http://localhost/medconnect/login.php
pause
