@echo off
echo ========================================
echo Quick MySQL Port Check
echo ========================================
echo.

echo Checking what's using port 3306...
echo.

netstat -ano | findstr :3306

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Port 3306 is OCCUPIED - this is why MySQL won't start!
    echo.
    echo To fix this:
    echo 1. Close the program using port 3306
    echo 2. Or run: taskkill /F /PID [process_id]
    echo 3. Then try starting MySQL again
) else (
    echo.
    echo Port 3306 is FREE - MySQL should be able to start
    echo.
    echo If MySQL still won't start, run: start_mysql.bat
)

echo.
pause
