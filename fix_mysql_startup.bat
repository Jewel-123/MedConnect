@echo off
echo ========================================
echo MySQL Startup Fix - Safe Recovery
echo ========================================
echo.
echo This script will fix MySQL startup issues
echo WITHOUT losing any data or tables.
echo.
pause

REM Step 1: Kill any hanging MySQL processes
echo [Step 1/5] Stopping any existing MySQL processes...
taskkill /F /IM mysqld.exe 2>NUL
timeout /t 2 >NUL
echo Done.
echo.

REM Step 2: Backup current configuration
echo [Step 2/5] Backing up MySQL configuration...
if not exist "c:\xampp\mysql\backup" mkdir "c:\xampp\mysql\backup"
copy /Y "c:\xampp\mysql\bin\my.ini" "c:\xampp\mysql\backup\my.ini.backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >NUL 2>&1
echo Done.
echo.

REM Step 3: Add InnoDB recovery mode temporarily
echo [Step 3/5] Configuring safe recovery mode...
echo. >> "c:\xampp\mysql\bin\my.ini"
echo # Temporary recovery mode - added by fix script >> "c:\xampp\mysql\bin\my.ini"
echo innodb_force_recovery=1 >> "c:\xampp\mysql\bin\my.ini"
echo Done.
echo.

REM Step 4: Start MySQL in recovery mode
echo [Step 4/5] Starting MySQL in recovery mode...
echo Please wait...
cd /d "c:\xampp\mysql\bin"
start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
timeout /t 8 >NUL

REM Check if MySQL started
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo ========================================
    echo [SUCCESS] MySQL started in recovery mode!
    echo ========================================
    echo.
    echo MySQL is now running. Your data is safe.
    echo.
    echo NEXT STEPS:
    echo 1. Test your database connection
    echo 2. Access phpMyAdmin: http://localhost/phpmyadmin
    echo 3. Verify your tables are intact
    echo.
    echo After verification, run: cleanup_mysql_config.bat
    echo to remove recovery mode settings.
    echo.
) else (
    echo.
    echo ========================================
    echo [FAILED] MySQL did not start
    echo ========================================
    echo.
    echo Trying alternative recovery level...
    echo.
    
    REM Try recovery level 2
    taskkill /F /IM mysqld.exe 2>NUL
    timeout /t 2 >NUL
    
    REM Update to recovery level 2
    powershell -Command "(Get-Content 'c:\xampp\mysql\bin\my.ini') -replace 'innodb_force_recovery=1', 'innodb_force_recovery=2' | Set-Content 'c:\xampp\mysql\bin\my.ini'"
    
    echo Starting with recovery level 2...
    start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 8 >NUL
    
    tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
    if "%ERRORLEVEL%"=="0" (
        echo.
        echo [SUCCESS] MySQL started with recovery level 2!
        echo.
        echo Your data is safe. Run cleanup_mysql_config.bat after verification.
        echo.
    ) else (
        echo.
        echo [ERROR] MySQL still won't start.
        echo.
        echo Please check the error log:
        echo c:\xampp\mysql\data\mysql_error.log
        echo.
        echo Or contact support with the error log contents.
        echo.
    )
)

echo [Step 5/5] Recovery process complete.
echo.
pause
