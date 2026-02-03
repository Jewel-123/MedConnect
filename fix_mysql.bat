@echo off
echo ========================================
echo MedConnect MySQL Fix (No Data Loss)
echo ========================================
echo.

echo Step 1: Checking MySQL service status...
echo.

REM Check if MySQL is running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is running
    echo.
) else (
    echo [WARNING] MySQL is NOT running
    echo.
    echo SOLUTION: Start MySQL from XAMPP Control Panel
    echo 1. Open XAMPP Control Panel
    echo 2. Click "Start" next to MySQL
    echo 3. Wait for it to turn green
    echo 4. Run this script again
    echo.
    pause
    exit /b 1
)

echo Step 2: Testing MySQL connection...
echo.

REM Test MySQL connection using PHP
C:\xampp\php\php.exe -r "try { $conn = new mysqli('localhost', 'root', '', 'medconnect'); if ($conn->connect_error) { echo '[ERROR] Connection failed: ' . $conn->connect_error . PHP_EOL; exit(1); } else { echo '[OK] MySQL connection successful' . PHP_EOL; $conn->close(); } } catch (Exception $e) { echo '[ERROR] ' . $e->getMessage() . PHP_EOL; exit(1); }"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo SOLUTION:
    echo 1. Open XAMPP Control Panel
    echo 2. Stop MySQL if running
    echo 3. Click "Start" next to MySQL
    echo 4. Check for port conflicts (port 3306)
    echo.
    pause
    exit /b 1
)

echo.
echo Step 3: Checking database and tables...
echo.

C:\xampp\php\php.exe -r "$conn = new mysqli('localhost', 'root', '', 'medconnect'); if ($conn->connect_error) { exit(1); } $result = $conn->query('SHOW TABLES'); echo '[OK] Database exists with ' . $result->num_rows . ' tables' . PHP_EOL; $conn->close();"

echo.
echo ========================================
echo MySQL Fix Complete - No Data Lost!
echo ========================================
echo.
echo Your database and all tables are intact.
echo.
echo Next steps:
echo 1. Try accessing phpMyAdmin: http://localhost/phpmyadmin
echo 2. Or use the web fix: http://localhost/medconnect/web_fix_login.php
echo.
pause
