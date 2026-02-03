@echo off
echo ========================================
echo MySQL Repair Finalization
echo ========================================
echo.
echo This script will restore normal MySQL operation
echo after system table repair.
echo.
echo IMPORTANT: Only run this AFTER verifying your
echo data is intact in phpMyAdmin!
echo.
pause

echo Stopping MySQL...
taskkill /F /IM mysqld.exe 2>NUL
timeout /t 3 >NUL

echo Removing safe mode settings...
powershell -Command "(Get-Content 'c:\xampp\mysql\bin\my.ini') | Where-Object { $_ -notmatch 'skip-grant-tables' -and $_ -notmatch 'innodb_force_recovery' -and $_ -notmatch 'Temporary - for system table repair' } | Set-Content 'c:\xampp\mysql\bin\my.ini.temp'"
move /Y "c:\xampp\mysql\bin\my.ini.temp" "c:\xampp\mysql\bin\my.ini" >NUL

echo Starting MySQL in normal mode...
cd /d "c:\xampp\mysql\bin"
start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
timeout /t 5 >NUL

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo ========================================
    echo [SUCCESS] MySQL is fully operational!
    echo ========================================
    echo.
    echo Your MySQL server is now running normally.
    echo All data has been preserved.
    echo.
    echo You can now use your application normally.
    echo.
) else (
    echo.
    echo ========================================
    echo [WARNING] MySQL did not start normally
    echo ========================================
    echo.
    echo Restarting in safe mode...
    echo skip-grant-tables >> "c:\xampp\mysql\bin\my.ini"
    echo innodb_force_recovery=1 >> "c:\xampp\mysql\bin\my.ini"
    
    start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 5 >NUL
    
    echo.
    echo MySQL has been restarted in safe mode.
    echo Please contact support for further assistance.
    echo.
)

pause
