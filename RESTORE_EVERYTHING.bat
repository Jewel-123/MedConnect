@echo off
echo ========================================
echo MEDCONNECT DATABASE RESTORATION
echo ========================================
echo.
echo This will:
echo 1. Set up all database tables
echo 2. Restore admin, doctors, and sample data
echo.
pause

cd /d "C:\xampp\htdocs\medconnect"

echo.
echo Step 1: Setting up database tables...
echo ========================================
"C:\xampp\mysql\bin\mysql.exe" -u root medconnect < consolidated_database_setup.sql
if %errorlevel% neq 0 (
    echo ERROR: Database setup failed!
    pause
    exit /b 1
)
echo SUCCESS: Database tables created!

echo.
echo Step 2: Restoring sample data...
echo ========================================
"C:\xampp\mysql\bin\mysql.exe" -u root medconnect < restore_sample_data.sql
if %errorlevel% neq 0 (
    echo ERROR: Data restoration failed!
    pause
    exit /b 1
)

echo.
echo ========================================
echo SUCCESS! Database fully restored!
echo ========================================
echo.
echo LOGIN CREDENTIALS:
echo.
echo Admin:
echo   Email: admin@medconnect.com
echo   Password: admin123
echo.
echo Sample Doctors (password: doctor123):
echo   - sarah.johnson@medconnect.com (Cardiologist)
echo   - michael.chen@medconnect.com (General Physician)
echo   - priya.sharma@medconnect.com (Dermatologist)
echo   - rajesh.kumar@medconnect.com (Orthopedist)
echo   - emily.white@medconnect.com (Pediatrician)
echo   - amit.patel@medconnect.com (Neurologist)
echo.
echo Sample Patients (password: patient123):
echo   - john.doe@example.com
echo   - jane.smith@example.com
echo   - rahul.verma@example.com
echo.
echo Pharmacy (password: doctor123):
echo   - apollo@pharmacy.com
echo.
echo ========================================
pause
