@echo off
COLOR 0A
echo.
echo ========================================
echo   XAMPP MySQL Complete Fix Tool
echo ========================================
echo.
echo This will fix MySQL start issues safely
echo (No data will be lost)
echo.
pause

echo.
echo [Step 1/4] Stopping any stuck MySQL processes...
taskkill /F /IM mysqld.exe 2>NUL
if %ERRORLEVEL% EQU 0 (
    echo    [OK] Stopped stuck MySQL process
    timeout /t 2 >NUL
) else (
    echo    [OK] No stuck processes found
)

echo.
echo [Step 2/4] Checking port 3306...
netstat -ano | findstr :3306 >NUL
if %ERRORLEVEL% EQU 0 (
    echo    [WARNING] Port 3306 is in use!
    echo.
    echo    Programs using port 3306:
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306') do (
        tasklist /FI "PID eq %%a" 2>NUL | findstr /V "Image Name"
    )
    echo.
    echo    Attempting to free the port...
    for /f "tokens=5" %%a in ('netstat -ano ^| findstr :3306') do (
        taskkill /F /PID %%a 2>NUL
    )
    timeout /t 2 >NUL
) else (
    echo    [OK] Port 3306 is available
)

echo.
echo [Step 3/4] Starting MySQL...
cd /d C:\xampp\mysql\bin
start "" "mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone

timeout /t 5 >NUL

echo.
echo [Step 4/4] Verifying MySQL is running...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if %ERRORLEVEL% EQU 0 (
    COLOR 0A
    echo.
    echo ========================================
    echo    SUCCESS! MySQL is now running
    echo ========================================
    echo.
    echo You can now:
    echo  - Access phpMyAdmin: http://localhost/phpmyadmin
    echo  - Setup login: http://localhost/medconnect/web_fix_login.php
    echo  - Check status: http://localhost/medconnect/check_mysql.php
    echo.
    echo The XAMPP Control Panel should now show MySQL as running.
    echo.
) else (
    COLOR 0C
    echo.
    echo ========================================
    echo    ERROR: MySQL failed to start
    echo ========================================
    echo.
    echo Possible causes:
    echo  1. Port 3306 is still blocked
    echo  2. MySQL configuration is corrupted
    echo  3. Insufficient permissions
    echo.
    echo Try this:
    echo  1. Run this script as Administrator (right-click, Run as admin)
    echo  2. Check error log: C:\xampp\mysql\data\mysql_error.log
    echo  3. Restart your computer
    echo.
)

pause
