<?php

$runtimeDir = __DIR__ . '/runtime';

if (!is_dir($runtimeDir)) {
    mkdir($runtimeDir, 0775, true);
}

putenv('DOTEITA_DB_FILE=' . $runtimeDir . '/database.sqlite');
putenv('DOTEITA_STORAGE_DIR=' . $runtimeDir . '/storage/data');

require_once __DIR__ . '/../db.php';
