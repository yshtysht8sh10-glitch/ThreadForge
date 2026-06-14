<?php

declare(strict_types=1);

putenv('THREADFORGE_FRONTEND_ID=materials-library');

$root = dirname(__DIR__);
require_once $root . '/server/db.php';
require_once $root . '/server/materials_legacy_import.php';

$pdo = getConnection();
$items = parseLegacyMaterialsDirectory($root . '/frontends/materials-library/legacy/05_Sozaiko');
$itemStmt = $pdo->prepare('SELECT id FROM material_items WHERE legacy_source = :source');
$answerStmt = $pdo->prepare(
    'SELECT t.label, mit.accepted
     FROM material_item_terms mit
     JOIN material_terms t ON t.id = mit.term_id
     WHERE mit.item_id = :item_id'
);
$mismatches = [];
$checkedAnswers = 0;

foreach ($items as $item) {
    $itemStmt->execute([':source' => $item['legacy_source']]);
    $itemId = $itemStmt->fetchColumn();
    if ($itemId === false) {
        $mismatches[] = $item['archive_relative'] . ': item missing';
        continue;
    }
    $answerStmt->execute([':item_id' => (int)$itemId]);
    $actual = [];
    foreach ($answerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $actual[(string)$row['label']] = (int)$row['accepted'];
    }
    foreach ($item['terms'] as $label => $answer) {
        $expected = $answer === '△' ? null : ($answer === '○' ? 1 : 0);
        $found = $actual[$label] ?? null;
        $checkedAnswers++;
        if ($found !== $expected) {
            $mismatches[] = implode(' | ', [
                $item['archive_relative'],
                $label,
                'legacy=' . $answer,
                'db=' . ($found === null ? '?' : (string)$found),
            ]);
        }
    }
}

echo 'items=' . count($items) . PHP_EOL;
echo 'answers=' . $checkedAnswers . PHP_EOL;
echo 'mismatches=' . count($mismatches) . PHP_EOL;
foreach ($mismatches as $mismatch) {
    echo $mismatch . PHP_EOL;
}
exit($mismatches === [] ? 0 : 1);
