@echo off
COLOR 0E
echo.
echo ========================================
echo   MySQL Auto-Fix Tool
echo ========================================
echo.
echo Detecting and fixing MySQL issue...
echo.

REM Step 1: Kill all MySQL processes
echo [1/5] Stopping MySQL processes...
taskkill /F /IM mysqld.exe >NUL 2>&1
timeout /t 2 >NUL
echo       Done

REM Step 2: Check and kill port 3306 users
echo [2/5] Freeing port 3306...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306 2^>NUL') do (
    taskkill /F /PID %%a >NUL 2>&1
)
timeout /t 1 >NUL
echo       Done

REM Step 3: Remove lock files
echo [3/5] Removing lock files...
del "C:\xampp\mysql\data\*.pid" >NUL 2>&1
del "C:\xampp\mysql\data\ibdata1.lock" >NUL 2>&1
del "C:\xampp\mysql\data\*.lock" >NUL 2>&1
echo       Done

REM Step 4: Fix permissions
echo [4/5] Fixing permissions...
icacls "C:\xampp\mysql\data" /grant Everyone:(OI)(CI)F /T >NUL 2>&1
echo       Done

REM Step 5: Start MySQL
echo [5/5] Starting MySQL...
cd /d C:\xampp\mysql\bin
start "" "mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone

echo       Waiting...
timeout /t 6 >NUL

REM Check if started
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe">NUL
if %ERRORLEVEL% EQU 0 (
    COLOR 0A
    echo.
    echo ========================================
    echo   SUCCESS! MySQL is Running!
    echo ========================================
    echo.
    echo You can now:
    echo   1. Open: http://localhost/phpmyadmin
    echo   2. Setup login: http://localhost/medconnect/web_fix_login.php
    echo.
    echo XAMPP Control Panel should show MySQL as running.
    echo.
) else (
    COLOR 0C
    echo.
    echo ========================================
    echo   MySQL Still Failed to Start
    echo ========================================
    echo.
    echo Checking error log...
    echo.
    
    REM Show actual error from log
    if exist "C:\xampp\mysql\data\mysql_error.log" (
        echo Last 20 lines of error log:
        echo ----------------------------------------
        powershell -Command "Get-Content 'C:\xampp\mysql\data\mysql_error.log' -Tail 20"
        echo ----------------------------------------
    ) else (
        echo Error log not found!
    )
    
    echo.
    echo Possible solutions:
    echo   1. Restart your computer
    echo   2. Run this script as Administrator
    echo   3. Check if antivirus is blocking MySQL
    echo   4. Try changing MySQL port to 3307
    echo.
)

pause
