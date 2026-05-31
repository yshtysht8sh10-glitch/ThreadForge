<?php

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
$logFile = $root . '/update_imported_recent.log';
$dbFile = is_file($root . '/db.php') ? $root . '/db.php' : $root . '/server/db.php';

function recentUpdateArchiveRoot(string $root): string
{
    $requested = $_GET['archive'] ?? '';
    if (is_string($requested) && trim($requested) !== '') {
        return trim($requested);
    }

    foreach (['legacy/import_data', 'legacy/data', 'import_data', 'data'] as $candidate) {
        if (is_dir($root . '/' . $candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException('archive directory not found');
}

try {
    if (!is_file($dbFile)) {
        throw new RuntimeException('db.php not found: ' . $dbFile);
    }

    require_once $dbFile;

    $archiveRoot = recentUpdateArchiveRoot($root);
    $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
    $apply = isset($_GET['apply']) && (string)$_GET['apply'] === '1';
    $add = isset($_GET['add']) && (string)$_GET['add'] === '1';

    $summary = updateImportedArchiveRecent(
        getConnection(),
        $archiveRoot,
        $limit,
        $apply,
        $add
    );

    $line = '[' . date('Y-m-d H:i:s') . '] '
        . 'archive=' . $archiveRoot
        . ' apply=' . ($apply ? 'yes' : 'no')
        . ' add=' . ($add ? 'yes' : 'no')
        . ' limit=' . ($summary['limit'] ?? $limit)
        . ' checked=' . ($summary['checked_threads'] ?? 0)
        . ' matched=' . ($summary['matched_threads'] ?? 0)
        . ' updated=' . ($summary['updated_threads'] ?? 0)
        . ' added_replies=' . ($summary['added_replies'] ?? 0)
        . ' missing=' . count($summary['missing_images'] ?? [])
        . ' new_candidates=' . count($summary['new_candidates'] ?? [])
        . ' inserted=' . count($summary['inserted_threads'] ?? [])
        . PHP_EOL;

    $details = '';

    foreach (($summary['changes'] ?? []) as $change) {
        $details .= '[' . date('Y-m-d H:i:s') . '] changed '
            . 'post_id=' . ($change['post_id'] ?? '')
            . ' title=' . str_replace(["\r", "\n"], ' ', (string)($change['title'] ?? ''))
            . ' created_at=' . ($change['created_at'] ?? '')
            . ' fields=' . implode(',', $change['fields'] ?? [])
            . ' added_replies=' . ($change['added_replies'] ?? 0)
            . ' source=' . ($change['source_file'] ?? '')
            . PHP_EOL;
    }

    foreach (($summary['new_candidates'] ?? []) as $candidate) {
        $details .= '[' . date('Y-m-d H:i:s') . '] new_candidate '
            . 'title=' . str_replace(["\r", "\n"], ' ', (string)($candidate['title'] ?? ''))
            . ' name=' . str_replace(["\r", "\n"], ' ', (string)($candidate['name'] ?? ''))
            . ' created_at=' . ($candidate['created_at'] ?? '')
            . ' source=' . ($candidate['file'] ?? '')
            . PHP_EOL;
    }

    foreach (($summary['inserted_threads'] ?? []) as $inserted) {
        $details .= '[' . date('Y-m-d H:i:s') . '] inserted '
            . 'post_id=' . ($inserted['post_id'] ?? '')
            . ' title=' . str_replace(["\r", "\n"], ' ', (string)($inserted['title'] ?? ''))
            . ' created_at=' . ($inserted['created_at'] ?? '')
            . ' source=' . ($inserted['source_file'] ?? '')
            . PHP_EOL;
    }

    foreach (($summary['missing_images'] ?? []) as $missing) {
        $details .= '[' . date('Y-m-d H:i:s') . '] missing_image '
            . str_replace(["\r", "\n"], ' ', (string)$missing)
            . PHP_EOL;
    }

    file_put_contents($logFile, $line . $details, FILE_APPEND);

    echo $line;
    echo $details;

    if (!$apply) {
        echo "Dry-run only. Add apply=1 to update matched posts.\n";
        echo "If new_candidates are truly new posts, use apply=1&add=1 to insert them.\n";
    } elseif ($apply && !$add && count($summary['new_candidates'] ?? []) > 0) {
        echo "Matched posts were updated. New candidates were NOT inserted because add=1 was not specified.\n";
    } elseif ($apply && $add) {
        echo "Matched posts updated. New candidates inserted when possible.\n";
    }
} catch (Throwable $exception) {
    $line = '[' . date('Y-m-d H:i:s') . '] error: ' . $exception->getMessage() . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    http_response_code(500);
    echo $line;
}