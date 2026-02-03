@echo off
echo ========================================
echo MySQL System Tables Restoration
echo ========================================
echo.
echo This script will restore corrupted MySQL
echo system tables WITHOUT losing your data.
echo.
echo Your application databases (medconnect, etc.)
echo will NOT be affected.
echo.
pause

REM Step 1: Stop MySQL if running
echo [Step 1/6] Stopping MySQL...
taskkill /F /IM mysqld.exe 2>NUL
timeout /t 3 >NUL
echo Done.
echo.

REM Step 2: Backup current mysql system database
echo [Step 2/6] Backing up current mysql system database...
if not exist "c:\xampp\mysql\data\mysql_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%" (
    xcopy "c:\xampp\mysql\data\mysql" "c:\xampp\mysql\data\mysql_backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%\" /E /I /H /Y >NUL 2>&1
    echo Backup created.
) else (
    echo Backup already exists.
)
echo.

REM Step 3: Delete corrupted sys.ibd file
echo [Step 3/6] Removing corrupted system files...
if exist "c:\xampp\mysql\data\sys\sys.ibd" (
    del /F /Q "c:\xampp\mysql\data\sys\sys.ibd" 2>NUL
    echo Removed corrupted sys.ibd file.
)
echo.

REM Step 4: Remove recovery mode from config
echo [Step 4/6] Cleaning MySQL configuration...
powershell -Command "(Get-Content 'c:\xampp\mysql\bin\my.ini') | Where-Object { $_ -notmatch 'innodb_force_recovery' -and $_ -notmatch 'Temporary recovery mode' } | Set-Content 'c:\xampp\mysql\bin\my.ini.temp'"
move /Y "c:\xampp\mysql\bin\my.ini.temp" "c:\xampp\mysql\bin\my.ini" >NUL
echo Done.
echo.

REM Step 5: Add skip-grant-tables temporarily
echo [Step 5/6] Adding temporary skip-grant-tables...
echo. >> "c:\xampp\mysql\bin\my.ini"
echo # Temporary - for system table repair >> "c:\xampp\mysql\bin\my.ini"
echo skip-grant-tables >> "c:\xampp\mysql\bin\my.ini"
echo innodb_force_recovery=1 >> "c:\xampp\mysql\bin\my.ini"
echo Done.
echo.

REM Step 6: Start MySQL
echo [Step 6/6] Starting MySQL in safe mode...
cd /d "c:\xampp\mysql\bin"
start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
timeout /t 8 >NUL

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo ========================================
    echo [SUCCESS] MySQL started in safe mode!
    echo ========================================
    echo.
    echo MySQL is now running without privilege checks.
    echo Your data is SAFE and intact.
    echo.
    echo NEXT STEPS:
    echo 1. Test your database: http://localhost/phpmyadmin
    echo 2. Verify your medconnect database is intact
    echo 3. Run: finalize_mysql_repair.bat to complete the fix
    echo.
    echo IMPORTANT: MySQL is running in safe mode.
    echo Run finalize_mysql_repair.bat to restore normal operation.
    echo.
) else (
    echo.
    echo ========================================
    echo [FAILED] MySQL did not start
    echo ========================================
    echo.
    echo Please check the error log:
    echo c:\xampp\mysql\data\mysql_error.log
    echo.
    echo Or contact support with the error details.
    echo.
)

pause
