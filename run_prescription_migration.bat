@echo off
echo ========================================
echo Running Prescription Workflow Migration
echo ========================================
echo.

cd /d "%~dp0"

echo Importing prescription workflow schema...
mysql -u root medconnect < prescription_workflow_schema.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Migration completed successfully!
    echo ========================================
    echo.
    echo Next steps:
    echo 1. Verify Central Pharmacy account created
    echo 2. Test prescription finalization
    echo 3. Check patient dashboard
    echo.
) else (
    echo.
    echo ========================================
    echo Migration failed! Please check errors above.
    echo ========================================
    echo.
)

pause
