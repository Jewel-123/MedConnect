@echo off
echo ========================================
echo MySQL Configuration Cleanup
echo ========================================
echo.
echo This script removes the temporary recovery
echo settings from MySQL configuration.
echo.
echo IMPORTANT: Only run this AFTER you have
echo verified that your data is intact!
echo.
pause

echo Stopping MySQL...
taskkill /F /IM mysqld.exe 2>NUL
timeout /t 3 >NUL

echo Removing recovery mode settings...
powershell -Command "(Get-Content 'c:\xampp\mysql\bin\my.ini') | Where-Object { $_ -notmatch 'innodb_force_recovery' -and $_ -notmatch 'Temporary recovery mode' } | Set-Content 'c:\xampp\mysql\bin\my.ini.temp'"
move /Y "c:\xampp\mysql\bin\my.ini.temp" "c:\xampp\mysql\bin\my.ini" >NUL

echo Starting MySQL in normal mode...
cd /d "c:\xampp\mysql\bin"
start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
timeout /t 5 >NUL

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo.
    echo ========================================
    echo [SUCCESS] MySQL is running normally!
    echo ========================================
    echo.
    echo Your MySQL is now fully operational.
    echo All recovery settings have been removed.
    echo.
) else (
    echo.
    echo [WARNING] MySQL did not start in normal mode.
    echo.
    echo Restoring recovery mode...
    echo innodb_force_recovery=1 >> "c:\xampp\mysql\bin\my.ini"
    
    start "" "c:\xampp\mysql\bin\mysqld.exe" --defaults-file="c:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 5 >NUL
    
    echo.
    echo MySQL has been restarted in recovery mode.
    echo Please export your data and contact support.
    echo.
)

pause
