@echo off
echo Checking for your data in all databases...
echo.

echo === Checking medconnect database ===
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT name, email, role FROM users WHERE email LIKE '%%smith%%' OR email LIKE '%%gmail%%' LIMIT 10;" medconnect 2>nul
if %errorlevel% equ 0 (
    echo Found data in medconnect!
) else (
    echo No data found in medconnect or table doesn't exist
)

echo.
echo === Checking medconnectnew database ===
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT name, email, role FROM users WHERE email LIKE '%%smith%%' OR email LIKE '%%gmail%%' LIMIT 10;" medconnectnew 2>nul
if %errorlevel% equ 0 (
    echo Found data in medconnectnew!
) else (
    echo No data found in medconnectnew or database doesn't exist
)

echo.
echo === Checking medconnect_new database ===
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT name, email, role FROM users WHERE email LIKE '%%smith%%' OR email LIKE '%%gmail%%' LIMIT 10;" medconnect_new 2>nul
if %errorlevel% equ 0 (
    echo Found data in medconnect_new!
) else (
    echo No data found in medconnect_new or database doesn't exist
)

echo.
echo === Listing all databases ===
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"

echo.
pause
