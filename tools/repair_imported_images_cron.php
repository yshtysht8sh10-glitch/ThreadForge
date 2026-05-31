<?php

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
$logFile = $root . '/repair_imported_images.log';
$stateFile = $root . '/repair_imported_images.offset';
$dbFile = is_file($root . '/db.php') ? $root . '/db.php' : $root . '/server/db.php';

function repairArchiveRoot(string $root): string
{
    $requested = $_GET['archive'] ?? '';
    if (is_string($requested) && trim($requested) !== '') {
        return trim($requested);
    }

    $candidates = [
        'legacy/import_data',
        'legacy/data',
        'import_data',
        'data',
    ];
    foreach ($candidates as $candidate) {
        if (is_dir($root . '/' . $candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException(
        'archive directory not found. checked: '
        . implode(', ', array_map(static fn(string $path): string => $root . '/' . $path, $candidates))
    );
}

try {
    if (!is_file($dbFile)) {
        throw new RuntimeException('db.php not found: ' . $dbFile);
    }
    require_once $dbFile;

    if (isset($_GET['reset'])) {
        @unlink($stateFile);
    }
    $offset = isset($_GET['offset'])
        ? max(0, (int)$_GET['offset'])
        : (is_file($stateFile) ? max(0, (int)trim((string)file_get_contents($stateFile))) : 0);
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 200;
    $pdo = getConnection();
    $archiveRoot = repairArchiveRoot($root);
    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] start archive=' . $archiveRoot . ' offset=' . $offset . ' limit=' . $limit . PHP_EOL,
        FILE_APPEND
    );
    $summary = repairLocalArchiveImages($pdo, $archiveRoot, true, $offset, $limit);
    $line = '[' . date('Y-m-d H:i:s') . '] '
        . 'archive=' . $archiveRoot
        . ' '
        . 'total=' . $summary['total_files']
        . ' offset=' . $summary['offset']
        . ' limit=' . $summary['limit']
        . ' next_offset=' . $summary['next_offset']
        . ' done=' . ($summary['done'] ? 'yes' : 'no')
        . ' '
        . 'checked=' . $summary['checked_threads']
        . ' matched=' . $summary['matched_threads']
        . ' fallback=' . $summary['fallback_matched_threads']
        . ' repaired=' . $summary['repaired_images']
        . ' missing=' . count($summary['missing_source_images'])
        . ' unmatched=' . count($summary['unmatched_threads'])
        . PHP_EOL;
    $details = '';
    foreach ($summary['changed_images'] as $change) {
        $details .= '[' . date('Y-m-d H:i:s') . '] changed '
            . 'post_id=' . $change['post_id']
            . ' title=' . str_replace(["\r", "\n"], ' ', (string)$change['title'])
            . ' created_at=' . $change['created_at']
            . ' source=' . $change['source']
            . ' destination=' . $change['destination']
            . PHP_EOL;
    }
    file_put_contents($logFile, $line . $details, FILE_APPEND);
    if ($summary['done']) {
        @unlink($stateFile);
    } else {
        file_put_contents($stateFile, (string)$summary['next_offset']);
    }
    echo $line;
    if (!$summary['done']) {
        echo 'Reload this URL to continue from offset ' . $summary['next_offset'] . ".\n";
    }
} catch (Throwable $exception) {
    $line = '[' . date('Y-m-d H:i:s') . '] error: ' . $exception->getMessage() . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    http_response_code(500);
    echo $line;
}
