@echo off
echo Running consolidated database setup...
cd /d "C:\xampp\htdocs\medconnect"
"C:\xampp\php\php.exe" run_consolidated_setup.php > setup_output.txt 2>&1
type setup_output.txt
pause
