<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$logFile = $root . '/import_threadforge_archives.log';
$lockFile = $root . '/import_threadforge_archives.lock';
$archiveDir = $root . '/legacy/import_data';
$dbFile = is_file($root . '/db.php') ? $root . '/db.php' : $root . '/server/db.php';

function cronImportLog(string $message): void
{
    global $logFile;
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

try {
    cronImportLog('start');

    if (file_exists($lockFile)) {
        cronImportLog('skip: lock file exists');
        exit(0);
    }
    file_put_contents($lockFile, (string)getmypid());

    if (!is_file($dbFile)) {
        throw new RuntimeException('db.php not found: ' . $dbFile);
    }
    if (!is_dir($archiveDir)) {
        throw new RuntimeException('archive directory not found: ' . $archiveDir);
    }

    require $dbFile;

    $result = importLocalArchiveTreeDirectory(getConnection(), $archiveDir);
    foreach ($result as $key => $value) {
        cronImportLog($key . ': ' . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value));
    }
    cronImportLog('done');
} catch (Throwable $exception) {
    cronImportLog('error: ' . $exception->getMessage());
    exit(1);
} finally {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
