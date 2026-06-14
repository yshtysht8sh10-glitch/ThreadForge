<?php

declare(strict_types=1);

putenv('THREADFORGE_FRONTEND_ID=materials-library');

$root = dirname(__DIR__);
require_once $root . '/server/db.php';
require_once $root . '/server/materials_legacy_import.php';

$legacyRoot = $argv[1] ?? ($root . '/frontends/materials-library/legacy/05_Sozaiko');
$dryRun = in_array('--dry-run', $argv, true);

try {
    $items = parseLegacyMaterialsDirectory($legacyRoot);
    if ($dryRun) {
        echo 'discovered: ' . count($items) . PHP_EOL;
        foreach ($items as $item) {
            echo implode("\t", [
                $item['archive_relative'],
                $item['tag_name'],
                $item['author_name'],
                $item['image_path'] === null ? 'no-preview' : basename($item['image_path']),
            ]) . PHP_EOL;
        }
        exit(0);
    }

    $summary = importLegacyMaterials(getConnection(), $legacyRoot);
    foreach (['discovered', 'imported', 'skipped', 'missing', 'supplemented', 'media_imported'] as $key) {
        echo $key . ': ' . $summary[$key] . PHP_EOL;
    }
    exit($summary['missing'] === 0 ? 0 : 2);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
