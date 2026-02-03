@echo off
echo ========================================
echo MySQL Error Log Viewer
echo ========================================
echo.

set ERROR_LOG=C:\xampp\mysql\data\mysql_error.log

if exist "%ERROR_LOG%" (
    echo Reading last 50 lines of error log...
    echo.
    echo ========================================
    powershell -Command "Get-Content '%ERROR_LOG%' -Tail 50"
    echo ========================================
) else (
    echo Error log not found at: %ERROR_LOG%
    echo.
    echo Checking alternative locations...
    dir C:\xampp\mysql\data\*.err /b 2>NUL
    if %ERRORLEVEL% EQU 0 (
        echo.
        echo Found error files above. Opening most recent...
        for /f %%i in ('dir C:\xampp\mysql\data\*.err /b /o-d') do (
            echo.
            echo File: %%i
            echo ========================================
            powershell -Command "Get-Content 'C:\xampp\mysql\data\%%i' -Tail 50"
            goto :done
        )
    ) else (
        echo No error logs found!
    )
)

:done
echo.
echo ========================================
echo.
echo Common MySQL startup errors:
echo.
echo 1. "Can't start server: Bind on TCP/IP port: Address already in use"
echo    FIX: Another program is using port 3306
echo.
echo 2. "InnoDB: Unable to lock ./ibdata1"
echo    FIX: Delete ibdata1.lock file or restart computer
echo.
echo 3. "Table 'mysql.plugin' doesn't exist"
echo    FIX: MySQL system tables are corrupted, need to reinstall
echo.
echo 4. "Can't create/write to file"
echo    FIX: Permission issue, run as Administrator
echo.
pause
