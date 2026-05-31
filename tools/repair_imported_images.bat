@echo off
setlocal
cd /d "%~dp0.."
php tools\repair_imported_images.php %*
