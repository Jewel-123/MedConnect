@echo off
echo ========================================
echo XAMPP MySQL Start Button Fix
echo ========================================
echo.

echo Checking what's blocking MySQL...
echo.

REM Check if MySQL is already running
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [INFO] MySQL process is already running
    echo Attempting to stop it first...
    taskkill /F /IM mysqld.exe 2>NUL
    timeout /t 2 >NUL
)

REM Check if port 3306 is in use
echo Checking port 3306...
netstat -ano | findstr :3306 >NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo [WARNING] Port 3306 is already in use!
    echo.
    echo Processes using port 3306:
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306') do (
        echo Process ID: %%a
        tasklist /FI "PID eq %%a" 2>NUL | findstr /V "Image"
    )
    echo.
    echo SOLUTION:
    echo 1. Close any other MySQL/MariaDB instances
    echo 2. Or change XAMPP MySQL port in my.ini
    echo.
) else (
    echo [OK] Port 3306 is available
    echo.
)

REM Try to start MySQL manually
echo Attempting to start MySQL manually...
echo.

cd /d C:\xampp\mysql\bin

echo Starting mysqld...
start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone --console

timeout /t 3 >NUL

REM Check if it started
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo ========================================
    echo [SUCCESS] MySQL started successfully!
    echo ========================================
    echo.
    echo You can now:
    echo 1. Access phpMyAdmin: http://localhost/phpmyadmin
    echo 2. Use the login fix: http://localhost/medconnect/check_mysql.php
    echo.
) else (
    echo.
    echo ========================================
    echo [ERROR] MySQL failed to start
    echo ========================================
    echo.
    echo Common causes:
    echo 1. Port 3306 is blocked by another program
    echo 2. MySQL configuration file is corrupted
    echo 3. Previous MySQL instance didn't close properly
    echo.
    echo SOLUTIONS:
    echo.
    echo Option 1: Kill all MySQL processes
    echo   taskkill /F /IM mysqld.exe
    echo.
    echo Option 2: Change MySQL port
    echo   Edit: C:\xampp\mysql\bin\my.ini
    echo   Change port=3306 to port=3307
    echo.
    echo Option 3: Check error log
    echo   Open: C:\xampp\mysql\data\mysql_error.log
    echo.
)

pause
