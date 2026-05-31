@echo off
setlocal
cd /d "%~dp0.."
php tools\update_imported_recent.php %*
