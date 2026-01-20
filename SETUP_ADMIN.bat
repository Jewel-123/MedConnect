@echo off
echo ========================================
echo SETTING UP ADMIN DASHBOARD
echo ========================================
echo Email: admin@medconnect.com
echo Password: admin123
echo ========================================
echo.

cd /d "C:\xampp\htdocs\medconnect"

echo Step 1: Resetting database...
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < reset_database.sql 2>&1"
if %errorlevel% neq 0 (
    echo Warning: Database reset had issues, continuing anyway...
)

echo Step 2: Creating tables...
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root medconnect < consolidated_database_setup.sql 2>&1"
if %errorlevel% neq 0 (
    echo Warning: Some tables may already exist, continuing...
)

echo Step 3: Creating admin and sample data...
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root medconnect < restore_sample_data.sql 2>&1"
if %errorlevel% neq 0 (
    echo ERROR: Failed to create admin user!
    pause
    exit /b 1
)

echo.
echo ========================================
echo SUCCESS! Admin dashboard is ready!
echo ========================================
echo.
echo Login at: http://localhost/medconnect/login.php
echo Email: admin@medconnect.com
echo Password: admin123
echo.
echo Opening admin dashboard...
start http://localhost/medconnect/login.php
echo.
pause
