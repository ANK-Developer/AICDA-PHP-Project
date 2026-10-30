@echo off
REM Runs the AICDA PHP project on this computer.
REM   Website: http://localhost:8080        Admin: http://localhost:8080/admin/login
REM   Database: MySQL 8.0 on port 3308 (Workbench: host 127.0.0.1, port 3308, user root, database aicda_php)
REM Close both windows to stop.

cd /d "%~dp0"
start "AICDA MySQL 8 (port 3308)" "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe" --defaults-file="%~dp0mysql8.ini" --console
timeout /t 5 >nul
start "AICDA PHP server (port 8080)" "C:\xampp\php\php.exe" -S localhost:8080 -t "..\AICDA-PHP\public_html" "%~dp0dev-router.php"
timeout /t 2 >nul
start "" http://localhost:8080
