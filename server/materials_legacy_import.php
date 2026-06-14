<?php

declare(strict_types=1);

function parseLegacyMaterialsDirectory(string $legacyRoot): array
{
    $root = realpath($legacyRoot);
    if ($root === false || !is_dir($root)) {
        throw new InvalidArgumentException('Legacy materials directory was not found: ' . $legacyRoot);
    }

    $htmlPath = $root . DIRECTORY_SEPARATOR . 'Sozaiko.html';
    if (!is_file($htmlPath)) {
        throw new InvalidArgumentException('Sozaiko.html was not found under: ' . $root);
    }

    $html = file_get_contents($htmlPath);
    if ($html === false) {
        throw new RuntimeException('Could not read: ' . $htmlPath);
    }

    $parts = preg_split(
        '/(<div\s+class="Heading1"[^>]*>.*?<\/div>|<table\s+class="CellssMaterialTable">)/isu',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );
    if (!is_array($parts)) {
        throw new RuntimeException('Could not parse the legacy materials HTML.');
    }

    $items = [];
    $currentTag = 'テンプレート・その他';
    $partCount = count($parts);
    for ($index = 0; $index < $partCount; $index++) {
        $part = $parts[$index];
        if (preg_match('/^<div\s+class="Heading1"/iu', $part) === 1) {
            $currentTag = legacyMaterialTagName(legacyMaterialPlainText($part));
            continue;
        }
        if (preg_match('/^<table\s+class="CellssMaterialTable"/iu', $part) !== 1) {
            continue;
        }

        $body = $parts[$index + 1] ?? '';
        if (preg_match('/href="\.\/zip\/([^"]+)"/iu', $body, $archiveMatch) !== 1) {
            continue;
        }

        $archiveRelative = legacyMaterialRelativePath(rawurldecode(html_entity_decode($archiveMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $imageRelative = null;
        if (preg_match('/(?:href|src)="\.\/img\/([^"]+)"/iu', $body, $imageMatch) === 1) {
            $imageRelative = legacyMaterialRelativePath(rawurldecode(html_entity_decode($imageMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        $mediaRelative = [];
        if (preg_match_all('/<audio[^>]+src="(?:\.\/)?img\/([^"]+)"/iu', $body, $audioMatches)) {
            foreach ($audioMatches[1] as $audioPath) {
                $mediaRelative[] = legacyMaterialRelativePath(rawurldecode(html_entity_decode($audioPath, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }
        }

        $archiveRelative = repairLegacyArchiveReference($root, $archiveRelative, $imageRelative);
        $terms = [];
        if (preg_match_all(
            '/<tr>\s*<td\s+class="TermsCellsLeft">(.*?)<\/td>\s*<td\s+class="TermsCellsRight">(.*?)<\/td>\s*<\/tr>/isu',
            $body,
            $termMatches,
            PREG_SET_ORDER
        )) {
            foreach ($termMatches as $termMatch) {
                $label = legacyMaterialPlainText($termMatch[1]);
                $answer = legacyMaterialPlainText($termMatch[2]);
                if ($label !== '') {
                    $terms[$label] = $answer;
                }
            }
        }

        $author = dirname(str_replace('\\', '/', $archiveRelative));
        if (preg_match('/<div\s+class="AuthorTableInCellssMaterialTable">(.*?)<\/div>/isu', $body, $authorMatch) === 1) {
            $author = legacyMaterialAuthorName(legacyMaterialPlainText($authorMatch[1]));
        }

        $notes = '';
        if (preg_match('/<div\s+class="CommentTableInCellssMaterialTable">(.*?)<\/div>/isu', $body, $commentMatch) === 1) {
            $notes = legacyMaterialMultilineText($commentMatch[1]);
            if ($notes === '－' || $notes === '-') {
                $notes = '';
            }
        }
        $partialTerms = array_keys(array_filter($terms, static fn(string $answer): bool => $answer === '△'));
        if ($partialTerms !== []) {
            $notes = trim($notes . "\n\n利用条件「△」: " . implode('、', $partialTerms));
        }

        $items[] = legacyMaterialParsedItem(
            $root,
            $archiveRelative,
            $imageRelative,
            $author,
            $currentTag,
            $notes,
            $terms,
            false,
            $mediaRelative
        );
    }

    $knownSources = [];
    foreach ($items as $item) {
        $knownSources[strtolower($item['archive_relative'])] = true;
    }

    $archiveRoot = $root . DIRECTORY_SEPARATOR . 'zip';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($archiveRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['zip', '7z', 'rar'], true)) {
            continue;
        }
        $relative = legacyMaterialRelativePath(substr($file->getPathname(), strlen($archiveRoot) + 1));
        if (isset($knownSources[strtolower($relative)])) {
            continue;
        }

        $authorDirectory = dirname(str_replace('\\', '/', $relative));
        $author = $authorDirectory === '00_global' ? '素材庫管理者' : basename($authorDirectory);
        $tag = $authorDirectory === 'CYAMON' ? 'エフェクト素材' : 'テンプレート・その他';
        $imageRelative = findLegacyPreviewForArchive($root, $relative);
        $items[] = legacyMaterialParsedItem(
            $root,
            $relative,
            $imageRelative,
            $author,
            $tag,
            '旧素材庫HTMLに未掲載のファイルから補完しました。',
            [],
            true,
            []
        );
        $knownSources[strtolower($relative)] = true;
    }

    usort($items, static fn(array $left, array $right): int =>
        [$left['tag_name'], strtolower($left['author_name']), strtolower($left['name'])]
        <=> [$right['tag_name'], strtolower($right['author_name']), strtolower($right['name'])]
    );

    return $items;
}

function importLegacyMaterials(PDO $pdo, string $legacyRoot, ?string $storageDir = null): array
{
    $items = parseLegacyMaterialsDirectory($legacyRoot);
    $storage = $storageDir ?? STORAGE_DIR;
    if (!is_dir($storage) && !mkdir($storage, 0775, true) && !is_dir($storage)) {
        throw new RuntimeException('Could not create storage directory: ' . $storage);
    }

    $summary = [
        'discovered' => count($items),
        'imported' => 0,
        'skipped' => 0,
        'missing' => 0,
        'supplemented' => 0,
        'media_imported' => 0,
        'items' => [],
    ];
    $existingStmt = $pdo->prepare('SELECT id FROM material_items WHERE legacy_source = :legacy_source');
    $insertStmt = $pdo->prepare(
        'INSERT INTO material_items
         (user_id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
          image_path, image_original_name, password_hash, legacy_source, created_at, updated_at)
         VALUES
         (null, :author_name, :name, :notes, :tag_id, "", :archive_original_name, :archive_size_bytes,
          null, :image_original_name, :password_hash, :legacy_source, :created_at, :updated_at)'
    );
    $updatePathsStmt = $pdo->prepare(
        'UPDATE material_items SET archive_path = :archive_path, image_path = :image_path WHERE id = :id'
    );
    $termInsertStmt = $pdo->prepare(
        'INSERT INTO material_item_terms (item_id, term_id, accepted) VALUES (:item_id, :term_id, :accepted)'
    );

    foreach ($items as $item) {
        $existingStmt->execute([':legacy_source' => $item['legacy_source']]);
        $existingId = $existingStmt->fetchColumn();
        if ($existingId !== false) {
            syncLegacyMaterialTerms($pdo, $item, (int)$existingId);
            $summary['media_imported'] += importLegacyMaterialMedia($pdo, $item, (int)$existingId, $storage);
            $summary['skipped']++;
            continue;
        }
        if (!is_file($item['archive_path'])) {
            $summary['missing']++;
            $summary['items'][] = ['status' => 'missing', 'source' => $item['archive_relative']];
            continue;
        }

        $tagId = ensureLegacyMaterialTag($pdo, $item['tag_name']);
        $createdAt = date('Y-m-d H:i:s', filemtime($item['archive_path']) ?: time());
        $copiedPaths = [];
        $pdo->beginTransaction();
        try {
            $insertStmt->execute([
                ':author_name' => $item['author_name'],
                ':name' => $item['name'],
                ':notes' => $item['notes'],
                ':tag_id' => $tagId,
                ':archive_original_name' => basename($item['archive_path']),
                ':archive_size_bytes' => filesize($item['archive_path']) ?: 0,
                ':image_original_name' => $item['image_path'] ? basename($item['image_path']) : null,
                ':password_hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                ':legacy_source' => $item['legacy_source'],
                ':created_at' => $createdAt,
                ':updated_at' => $createdAt,
            ]);
            $itemId = (int)$pdo->lastInsertId();
            $archiveExtension = strtolower(pathinfo($item['archive_path'], PATHINFO_EXTENSION));
            $archiveDestination = $storage . DIRECTORY_SEPARATOR . 'material-' . $itemId . '-archive.' . $archiveExtension;
            legacyMaterialCopy($item['archive_path'], $archiveDestination);
            $copiedPaths[] = $archiveDestination;

            $imageDestination = null;
            if ($item['image_path'] !== null && is_file($item['image_path'])) {
                $imageExtension = strtolower(pathinfo($item['image_path'], PATHINFO_EXTENSION));
                $imageDestination = $storage . DIRECTORY_SEPARATOR . 'material-' . $itemId . '-image.' . $imageExtension;
                legacyMaterialCopy($item['image_path'], $imageDestination);
                $copiedPaths[] = $imageDestination;
            }
            $updatePathsStmt->execute([
                ':archive_path' => $archiveDestination,
                ':image_path' => $imageDestination,
                ':id' => $itemId,
            ]);

            foreach ($item['terms'] as $label => $answer) {
                $termId = ensureLegacyMaterialTerm($pdo, $label);
                if ($answer === '△') {
                    continue;
                }
                $termInsertStmt->execute([
                    ':item_id' => $itemId,
                    ':term_id' => $termId,
                    ':accepted' => $answer === '○' ? 1 : 0,
                ]);
            }
            $pdo->commit();
            $summary['imported']++;
            if ($item['supplemented']) {
                $summary['supplemented']++;
            }
            $summary['media_imported'] += importLegacyMaterialMedia($pdo, $item, $itemId, $storage);
            $summary['items'][] = ['status' => 'imported', 'id' => $itemId, 'source' => $item['archive_relative']];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($copiedPaths as $copiedPath) {
                if (is_file($copiedPath)) {
                    unlink($copiedPath);
                }
            }
            throw $exception;
        }
    }

    normalizeLegacyMaterialTerms($pdo);
    return $summary;
}

function syncLegacyMaterialTerms(PDO $pdo, array $item, int $itemId): void
{
    $delete = $pdo->prepare(
        'DELETE FROM material_item_terms WHERE item_id = :item_id AND term_id = :term_id'
    );
    $insert = $pdo->prepare(
        'INSERT INTO material_item_terms (item_id, term_id, accepted) VALUES (:item_id, :term_id, :accepted)'
    );
    foreach ($item['terms'] as $label => $answer) {
        $termId = ensureLegacyMaterialTerm($pdo, $label);
        $delete->execute([':item_id' => $itemId, ':term_id' => $termId]);
        if ($answer === '△') {
            continue;
        }
        $insert->execute([
            ':item_id' => $itemId,
            ':term_id' => $termId,
            ':accepted' => $answer === '○' ? 1 : 0,
        ]);
    }
}

function normalizeLegacyMaterialTerms(PDO $pdo): void
{
    $labels = [
        '改変',
        '2次配布',
        'readmeへの記載しなくてよい',
        'mugen以外での利用（非営利目的）',
        'mugen以外での利用（営利目的）',
    ];
    $update = $pdo->prepare('UPDATE material_terms SET sort_order = :sort_order WHERE label = :label');
    foreach ($labels as $sortOrder => $label) {
        $update->execute([':sort_order' => $sortOrder, ':label' => $label]);
    }

    $delete = $pdo->prepare(
        'DELETE FROM material_terms
         WHERE label = :label
           AND NOT EXISTS (SELECT 1 FROM material_item_terms WHERE term_id = material_terms.id)'
    );
    foreach (['二次配布', 'Readmeへの記載', '商用利用'] as $label) {
        $delete->execute([':label' => $label]);
    }
}

function legacyMaterialParsedItem(
    string $root,
    string $archiveRelative,
    ?string $imageRelative,
    string $author,
    string $tag,
    string $notes,
    array $terms,
    bool $supplemented,
    array $mediaRelative
): array {
    $archivePath = $root . DIRECTORY_SEPARATOR . 'zip' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $archiveRelative);
    $imagePath = $imageRelative === null
        ? null
        : $root . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imageRelative);
    return [
        'legacy_source' => 'legacy-sozaiko:' . strtolower(str_replace('\\', '/', $archiveRelative)),
        'archive_relative' => str_replace('\\', '/', $archiveRelative),
        'archive_path' => $archivePath,
        'image_path' => $imagePath !== null && is_file($imagePath) ? $imagePath : null,
        'author_name' => $author !== '' ? $author : '作者不明',
        'name' => pathinfo(basename($archiveRelative), PATHINFO_FILENAME),
        'tag_name' => $tag,
        'notes' => $notes,
        'terms' => $terms,
        'supplemented' => $supplemented,
        'media_paths' => array_values(array_filter(array_map(
            static fn(string $relative): ?string => is_file($root . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))
                ? $root . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative)
                : null,
            $mediaRelative
        ))),
    ];
}

function importLegacyMaterialMedia(PDO $pdo, array $item, int $itemId, string $storage): int
{
    $paths = $item['media_paths'] ?? [];
    if ($paths === [] || (int)$pdo->query('SELECT COUNT(*) FROM material_media WHERE item_id = ' . $itemId)->fetchColumn() > 0) {
        return 0;
    }
    $insert = $pdo->prepare(
        'INSERT INTO material_media (item_id, path, original_name, size_bytes, sort_order, created_at)
         VALUES (:item_id, :path, :original_name, :size_bytes, :sort_order, :created_at)'
    );
    $count = 0;
    foreach ($paths as $index => $path) {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $destination = $storage . DIRECTORY_SEPARATOR . 'material-' . $itemId . '-audio-' . $index . '.' . $extension;
        legacyMaterialCopy($path, $destination);
        $insert->execute([
            ':item_id' => $itemId,
            ':path' => $destination,
            ':original_name' => basename($path),
            ':size_bytes' => filesize($path) ?: 0,
            ':sort_order' => $index,
            ':created_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
        ]);
        $count++;
    }
    return $count;
}

function repairLegacyArchiveReference(string $root, string $archiveRelative, ?string $imageRelative): string
{
    $current = $root . DIRECTORY_SEPARATOR . 'zip' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $archiveRelative);
    if (is_file($current) && $imageRelative === null) {
        return $archiveRelative;
    }

    if ($imageRelative !== null) {
        $directory = dirname(str_replace('\\', '/', $archiveRelative));
        $stem = pathinfo(basename($imageRelative), PATHINFO_FILENAME);
        foreach (['zip', '7z', 'rar'] as $extension) {
            $candidate = ($directory === '.' ? '' : $directory . '/') . $stem . '.' . $extension;
            $candidatePath = $root . DIRECTORY_SEPARATOR . 'zip' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($candidatePath)) {
                return $candidate;
            }
        }
    }
    return $archiveRelative;
}

function findLegacyPreviewForArchive(string $root, string $archiveRelative): ?string
{
    $directory = dirname(str_replace('\\', '/', $archiveRelative));
    $stem = pathinfo(basename($archiveRelative), PATHINFO_FILENAME);
    foreach (['gif', 'png', 'jpg', 'jpeg', 'webp'] as $extension) {
        $candidate = ($directory === '.' ? '' : $directory . '/') . $stem . '.' . $extension;
        $path = $root . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
        if (is_file($path)) {
            return $candidate;
        }
    }
    return null;
}

function legacyMaterialTagName(string $heading): string
{
    if (str_contains($heading, 'キャラクタースプライト')) {
        return 'キャラクタースプライト素材';
    }
    if (str_contains($heading, 'ドット絵')) {
        return 'ドット絵素材';
    }
    if (str_contains($heading, '効果音') || str_contains($heading, 'ボイス')) {
        return '音声・ボイス素材';
    }
    if (str_contains($heading, 'エフェクト')) {
        return 'エフェクト素材';
    }
    return 'テンプレート・その他';
}

function legacyMaterialAuthorName(string $value): string
{
    $value = preg_replace('/^作者\s*[：:]\s*/u', '', trim($value)) ?? trim($value);
    return preg_replace('/氏$/u', '', $value) ?? $value;
}

function legacyMaterialPlainText(string $html): string
{
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
}

function legacyMaterialMultilineText(string $html): string
{
    $text = preg_replace('/<br\s*\/?>/iu', "\n", $html) ?? $html;
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lines = preg_split('/\R/u', $text) ?: [];
    return trim(implode("\n", array_map('trim', $lines)));
}

function legacyMaterialRelativePath(string $path): string
{
    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    if ($normalized === '' || str_contains($normalized, '../') || str_contains($normalized, "\0")) {
        throw new InvalidArgumentException('Unsafe legacy material path: ' . $path);
    }
    return $normalized;
}

function ensureLegacyMaterialTag(PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM material_tags WHERE name = :name');
    $stmt->execute([':name' => $name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }
    $sortOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM material_tags')->fetchColumn();
    $stmt = $pdo->prepare(
        'INSERT INTO material_tags (name, sort_order, created_at) VALUES (:name, :sort_order, :created_at)'
    );
    $stmt->execute([':name' => $name, ':sort_order' => $sortOrder, ':created_at' => currentTimestamp()]);
    return (int)$pdo->lastInsertId();
}

function ensureLegacyMaterialTerm(PDO $pdo, string $label): int
{
    $stmt = $pdo->prepare('SELECT id FROM material_terms WHERE label = :label ORDER BY id LIMIT 1');
    $stmt->execute([':label' => $label]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }
    $sortOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM material_terms')->fetchColumn();
    $stmt = $pdo->prepare(
        'INSERT INTO material_terms (label, description, sort_order, created_at)
         VALUES (:label, "", :sort_order, :created_at)'
    );
    $stmt->execute([':label' => $label, ':sort_order' => $sortOrder, ':created_at' => currentTimestamp()]);
    return (int)$pdo->lastInsertId();
}

function legacyMaterialCopy(string $source, string $destination): void
{
    if (!copy($source, $destination)) {
        throw new RuntimeException('Could not copy legacy material: ' . $source);
    }
}
