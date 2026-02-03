@echo off
echo ========================================
echo Executing Prescription Workflow Schema
echo ========================================
echo.

cd /d "C:\xampp\mysql\bin"

echo Executing prescription_workflow_schema.sql...
mysql -u root medconnect < "C:\xampp\htdocs\medconnect\prescription_workflow_schema.sql"
if %errorlevel% neq 0 (
    echo ERROR: Failed to execute prescription_workflow_schema.sql
    pause
    exit /b 1
)
echo SUCCESS: Prescription workflow schema executed
echo.

echo Executing pharmacy_medicines_seed.sql...
mysql -u root medconnect < "C:\xampp\htdocs\medconnect\pharmacy_medicines_seed.sql"
if %errorlevel% neq 0 (
    echo ERROR: Failed to execute pharmacy_medicines_seed.sql
    pause
    exit /b 1
)
echo SUCCESS: Medicine inventory seeded
echo.

echo Executing prescription_reviews_schema.sql...
mysql -u root medconnect < "C:\xampp\htdocs\medconnect\prescription_reviews_schema.sql"
if %errorlevel% neq 0 (
    echo ERROR: Failed to execute prescription_reviews_schema.sql
    pause
    exit /b 1
)
echo SUCCESS: Reviews schema created
echo.

echo ========================================
echo All schemas executed successfully!
echo ========================================
echo.
echo Verifying changes...
mysql -u root medconnect -e "SHOW COLUMNS FROM prescriptions_v2 LIKE 'status';"
mysql -u root medconnect -e "SELECT COUNT(*) as medicine_count FROM pharmacy_inventory;"
mysql -u root medconnect -e "SHOW TABLES LIKE 'prescription_reviews';"
echo.
echo Press any key to exit...
pause >nul
