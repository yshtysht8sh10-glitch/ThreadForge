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
$add = in_array('--add', $argv, true);
$limit = 10;
$archiveRoot = 'legacy/import_data';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    } elseif (!in_array($arg, ['--apply', '--add'], true)) {
        $archiveRoot = $arg;
    }
}

$summary = updateImportedArchiveRecent(getConnection(), $archiveRoot, $limit, $apply, $add);

echo ($apply ? "Recent imported archive update applied.\n" : "Recent imported archive update dry-run.\n");
echo "Archive root: {$archiveRoot}\n";
echo 'Limit: ' . $summary['limit'] . "\n";
echo 'Add new threads: ' . ($add ? 'yes' : 'no') . "\n";
echo 'Checked threads: ' . $summary['checked_threads'] . "\n";
echo 'Matched threads: ' . $summary['matched_threads'] . "\n";
echo 'Updated threads: ' . $summary['updated_threads'] . "\n";
echo 'Added replies: ' . $summary['added_replies'] . "\n";
echo 'Skipped existing replies: ' . $summary['skipped_existing_replies'] . "\n";
echo 'Missing images: ' . count($summary['missing_images'] ?? []) . "\n";
echo 'New candidates: ' . count($summary['new_candidates'] ?? []) . "\n";
echo 'Inserted threads: ' . count($summary['inserted_threads'] ?? []) . "\n";

if ($summary['changes'] !== []) {
    echo "\nChanges:\n";
    foreach ($summary['changes'] as $change) {
        echo '- post_id=' . $change['post_id']
            . ' title=' . $change['title']
            . ' created_at=' . $change['created_at']
            . ' fields=' . implode(',', $change['fields'])
            . ' added_replies=' . $change['added_replies']
            . ' source=' . $change['source_file']
            . "\n";
    }
}

if (($summary['new_candidates'] ?? []) !== []) {
    echo "\nNew candidates:\n";
    foreach ($summary['new_candidates'] as $candidate) {
        echo '- title=' . $candidate['title']
            . ' name=' . $candidate['name']
            . ' created_at=' . $candidate['created_at']
            . ' source=' . $candidate['file']
            . "\n";
    }
}

if (($summary['inserted_threads'] ?? []) !== []) {
    echo "\nInserted threads:\n";
    foreach ($summary['inserted_threads'] as $inserted) {
        echo '- post_id=' . $inserted['post_id']
            . ' title=' . $inserted['title']
            . ' created_at=' . $inserted['created_at']
            . ' source=' . $inserted['source_file']
            . "\n";
    }
}

if (!$apply) {
    echo "\nNo files were changed. Add --apply to update matched posts.\n";
    echo "If new candidates are truly new posts, use --apply --add to insert them.\n";
} elseif (!$add && ($summary['new_candidates'] ?? []) !== []) {
    echo "\nMatched posts were updated. New candidates were NOT inserted because --add was not specified.\n";
}
