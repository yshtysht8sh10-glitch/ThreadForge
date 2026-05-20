<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$dbFile = is_file($root . '/db.php') ? $root . '/db.php' : $root . '/server/db.php';

require $dbFile;

$dir = $argv[1] ?? 'data';

try {
    $result = importLocalArchiveDirectory(getConnection(), $dir);
    foreach ($result as $key => $value) {
        if (is_array($value)) {
            echo $key . ': ' . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            continue;
        }
        echo $key . ': ' . $value . PHP_EOL;
    }
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
