@echo off
setlocal

cd /d "%~dp0"

set "ARCHIVE_DIR=%~1"
if "%ARCHIVE_DIR%"=="" set "ARCHIVE_DIR=data"

set "PHP_EXE=server\.php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

"%PHP_EXE%" -r "require getcwd() . '/server/db.php'; $dir = $argv[1] ?? 'data'; try { $result = importLocalArchiveDirectory(getConnection(), $dir); foreach ($result as $key => $value) { if (is_array($value)) { echo $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL; } else { echo $key . ': ' . $value . PHP_EOL; } } exit(0); } catch (Throwable $exception) { fwrite(STDERR, $exception->getMessage() . PHP_EOL); exit(1); }" "%ARCHIVE_DIR%"
set "IMPORT_EXIT=%ERRORLEVEL%"

endlocal & exit /b %IMPORT_EXIT%
