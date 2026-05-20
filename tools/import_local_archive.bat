@echo off
setlocal

cd /d "%~dp0\.."

set "ARCHIVE_DIR=%~1"
if "%ARCHIVE_DIR%"=="" set "ARCHIVE_DIR=data"

set "PHP_EXE=server\.php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

"%PHP_EXE%" tools\import_local_archive.php "%ARCHIVE_DIR%"
set "IMPORT_EXIT=%ERRORLEVEL%"

endlocal & exit /b %IMPORT_EXIT%
