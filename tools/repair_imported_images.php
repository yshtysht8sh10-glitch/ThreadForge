<?php

declare(strict_types=1);

$root = dirname(__DIR__);
chdir($root);
$dbFile = is_file($root . '/db.php') ? $root . '/db.php' : $root . '/server/db.php';
if (!is_file($dbFile)) {
    throw new RuntimeException('db.php not found: ' . $dbFile);
}
require_once $dbFile;

$apply = in_array('--apply', $argv, true);
$archiveRoot = 'legacy/import_data';
$offset = 0;
$limit = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--offset=')) {
        $offset = max(0, (int)substr($arg, 9));
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    } elseif ($arg !== '--apply') {
        $archiveRoot = $arg;
    }
}

$pdo = getConnection();
$summary = repairLocalArchiveImages($pdo, $archiveRoot, $apply, $offset, $limit);

echo ($apply ? "Imported image repair applied.\n" : "Imported image repair dry-run.\n");
echo "Archive root: {$archiveRoot}\n";
echo 'Total log files: ' . $summary['total_files'] . "\n";
echo 'Offset: ' . $summary['offset'] . "\n";
echo 'Limit: ' . ($summary['limit'] ?? 'none') . "\n";
echo 'Next offset: ' . $summary['next_offset'] . "\n";
echo 'Done: ' . ($summary['done'] ? 'yes' : 'no') . "\n";
echo 'Checked threads: ' . $summary['checked_threads'] . "\n";
echo 'Matched threads: ' . $summary['matched_threads'] . "\n";
echo 'Fallback matched threads: ' . $summary['fallback_matched_threads'] . "\n";
echo 'Images ' . ($apply ? 'repaired' : 'repairable') . ': ' . $summary['repaired_images'] . "\n";
echo 'Skipped no-image threads: ' . $summary['skipped_no_image'] . "\n";
echo 'Missing source images: ' . count($summary['missing_source_images']) . "\n";
echo 'Unmatched threads: ' . count($summary['unmatched_threads']) . "\n";

if ($summary['changed_images'] !== []) {
    echo "\nChanged images:\n";
    foreach ($summary['changed_images'] as $change) {
        echo '- post_id=' . $change['post_id']
            . ' title=' . $change['title']
            . ' created_at=' . $change['created_at']
            . ' source=' . $change['source']
            . ' destination=' . $change['destination']
            . "\n";
    }
}

if (!$apply) {
    echo "\nNo files were changed. Add --apply to copy images and update DB image_path values.\n";
}
