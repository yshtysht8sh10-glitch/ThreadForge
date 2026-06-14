@echo off
setlocal
set ROOT=%~dp0..
"%ROOT%\server\.php\php.exe" "%ROOT%\tools\import_legacy_materials.php" %*
endlocal
