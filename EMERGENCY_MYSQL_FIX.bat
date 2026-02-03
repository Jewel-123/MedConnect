@echo off
echo ========================================
echo MySQL Emergency Recovery
echo ========================================
echo.
echo This will attempt to recover MySQL
echo WARNING: This may take a few minutes
echo.
pause

echo.
echo [1/6] Stopping all MySQL processes...
taskkill /F /IM mysqld.exe 2>NUL
timeout /t 2 >NUL

echo.
echo [2/6] Checking for lock files...
if exist "C:\xampp\mysql\data\ibdata1.lock" (
    echo    Found ibdata1.lock - deleting...
    del "C:\xampp\mysql\data\ibdata1.lock" 2>NUL
)
if exist "C:\xampp\mysql\data\*.pid" (
    echo    Found PID files - deleting...
    del "C:\xampp\mysql\data\*.pid" 2>NUL
)

echo.
echo [3/6] Checking permissions...
icacls "C:\xampp\mysql\data" /grant Everyone:(OI)(CI)F /T >NUL 2>&1
echo    Permissions updated

echo.
echo [4/6] Backing up error log...
if exist "C:\xampp\mysql\data\mysql_error.log" (
    copy "C:\xampp\mysql\data\mysql_error.log" "C:\xampp\mysql\data\mysql_error_backup.log" >NUL 2>&1
    echo    Backup created
)

echo.
echo [5/6] Starting MySQL with recovery mode...
cd /d C:\xampp\mysql\bin
start "" "mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone --console

echo    Waiting for MySQL to start...
timeout /t 8 >NUL

echo.
echo [6/6] Checking if MySQL started...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if %ERRORLEVEL% EQU 0 (
    COLOR 0A
    echo.
    echo ========================================
    echo    SUCCESS! MySQL is running!
    echo ========================================
    echo.
) else (
    COLOR 0C
    echo.
    echo ========================================
    echo    FAILED - MySQL did not start
    echo ========================================
    echo.
    echo Next steps:
    echo 1. Run: view_mysql_errors.bat
    echo 2. Check what the error says
    echo 3. Share the error with me for specific fix
    echo.
)

pause
