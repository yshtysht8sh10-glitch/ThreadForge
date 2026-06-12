<?php

$runtimeDir = __DIR__ . '/uploader-runtime';

if (!is_dir($runtimeDir)) {
    mkdir($runtimeDir, 0775, true);
}

putenv('THREADFORGE_FRONTEND_ID=file-uploader');
putenv('THREADFORGE_DB_FILE=' . $runtimeDir . '/database.sqlite');
putenv('THREADFORGE_STORAGE_DIR=' . $runtimeDir . '/storage/data');

require_once __DIR__ . '/../db.php';
