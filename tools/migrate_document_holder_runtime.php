<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$legacyRoot = $root . '/frontends/document-holder/legacy/04_DMF';
$dbPath = $root . '/server/runtime/document-holder/database.sqlite';
$storageDir = $root . '/server/runtime/document-holder/storage/data';
$htmlPrefix = '<!-- threadforge-document-html -->';

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("ATTACH DATABASE ':memory:' AS mem");

ensureColumns($pdo);
normalizeTags($pdo);
updateSettings($pdo, $legacyRoot);

$records = collectMenuRecords($legacyRoot);
$updated = 0;
foreach ($records as $record) {
    $sourcePath = $legacyRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $record['href']);
    if (!is_file($sourcePath)) {
        fwrite(STDERR, "missing source: {$record['href']}\n");
        continue;
    }

    $stmt = $pdo->prepare('SELECT id, archive_path FROM material_items WHERE name = :name AND author_name = :author LIMIT 1');
    $stmt->execute([':name' => $record['title'], ':author' => $record['author']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        fwrite(STDERR, "missing item: {$record['title']} / {$record['author']}\n");
        continue;
    }

    $itemId = (int)$item['id'];
    $sourceDir = dirname($sourcePath);
    $html = rewriteArticleHtml((string)file_get_contents($sourcePath), $sourceDir, $legacyRoot);
    $archivePath = (string)($item['archive_path'] ?: $storageDir . "/material-{$itemId}-archive.zip");
    if (!is_dir(dirname($archivePath))) {
        mkdir(dirname($archivePath), 0777, true);
    }
    createArticleZip($archivePath, $sourceDir, $sourcePath);
    $date = date('Y-m-d H:i:s', filemtime($sourcePath) ?: time());
    $tagId = ensureLegacySubTag($pdo, $record['category']);

    $update = $pdo->prepare(
        'UPDATE material_items
         SET notes = :notes, tag_id = :tag_id, archive_path = :archive_path,
             archive_original_name = :archive_name, archive_size_bytes = :archive_size,
             created_at = :created_at, updated_at = :updated_at
         WHERE id = :id'
    );
    $update->execute([
        ':notes' => $htmlPrefix . $html,
        ':tag_id' => $tagId,
        ':archive_path' => $archivePath,
        ':archive_name' => safeName(basename($sourceDir)) . '.zip',
        ':archive_size' => filesize($archivePath) ?: 0,
        ':created_at' => $date,
        ':updated_at' => $date,
        ':id' => $itemId,
    ]);
    $updated++;
}

cleanupImportedAuthorTags($pdo);

echo "updated: {$updated}\n";

function ensureColumns(PDO $pdo): void
{
    $columns = array_column($pdo->query('PRAGMA table_info(material_items)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('draft', $columns, true)) {
        $pdo->exec('ALTER TABLE material_items ADD COLUMN draft INTEGER NOT NULL DEFAULT 0');
    }
    if (!in_array('view_count', $columns, true)) {
        $pdo->exec('ALTER TABLE material_items ADD COLUMN view_count INTEGER NOT NULL DEFAULT 0');
    }
    $tagColumns = array_column($pdo->query('PRAGMA table_info(material_tags)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('parent_id', $tagColumns, true)) {
        $pdo->exec('ALTER TABLE material_tags ADD COLUMN parent_id INTEGER');
    }
}

function normalizeTags(PDO $pdo): void
{
    foreach (['指南', '感想文', 'ドキュメント', 'その他'] as $index => $name) {
        $id = tagIdByName($pdo, $name);
        if (!$id) {
            $stmt = $pdo->prepare('INSERT INTO material_tags (name, parent_id, sort_order, created_at) VALUES (:name, null, :sort_order, :created_at)');
            $stmt->execute([':name' => $name, ':sort_order' => $index, ':created_at' => date('Y-m-d H:i:s')]);
        } else {
            $stmt = $pdo->prepare('UPDATE material_tags SET parent_id = null, sort_order = :sort_order WHERE id = :id');
            $stmt->execute([':sort_order' => $index, ':id' => $id]);
        }
    }
    foreach (['スタート', 'スプライト関連', 'システム関連', 'サウンド関連', '他'] as $index => $name) {
        ensureLegacySubTag($pdo, $name, $index);
    }
    $otherId = tagIdByName($pdo, 'その他');
    $allowedPrimary = ['指南', '感想文', 'ドキュメント', 'その他'];
    if ($otherId) {
        $stmt = $pdo->prepare(
            'UPDATE material_tags
             SET parent_id = :parent_id
             WHERE parent_id IS NULL
               AND name NOT IN ("指南", "感想文", "ドキュメント", "その他")'
        );
        $stmt->execute([':parent_id' => $otherId]);
    }
}

function cleanupImportedAuthorTags(PDO $pdo): void
{
    $guideId = tagIdByName($pdo, '指南');
    if (!$guideId) {
        return;
    }
    $keep = ['スタート', 'スプライト関連', 'システム関連', 'サウンド関連', '他'];
    $placeholders = implode(',', array_fill(0, count($keep), '?'));
    $sql = "DELETE FROM material_tags
            WHERE parent_id = ?
              AND name NOT IN ({$placeholders})
              AND id NOT IN (SELECT DISTINCT tag_id FROM material_items)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$guideId, ...$keep]);
}

function ensureLegacySubTag(PDO $pdo, string $name, ?int $sortOrder = null): int
{
    $guideId = tagIdByName($pdo, '指南');
    if (!$guideId) {
        throw new RuntimeException('指南タグがありません。');
    }
    $id = tagIdByName($pdo, $name);
    if (!$id) {
        $stmt = $pdo->prepare('INSERT INTO material_tags (name, parent_id, sort_order, created_at) VALUES (:name, :parent_id, :sort_order, :created_at)');
        $stmt->execute([':name' => $name, ':parent_id' => $guideId, ':sort_order' => $sortOrder ?? 0, ':created_at' => date('Y-m-d H:i:s')]);
        return (int)$pdo->lastInsertId();
    }
    $stmt = $pdo->prepare('UPDATE material_tags SET parent_id = :parent_id, sort_order = :sort_order WHERE id = :id');
    $stmt->execute([':parent_id' => $guideId, ':sort_order' => $sortOrder ?? (int)$id, ':id' => $id]);
    return (int)$id;
}

function tagIdByName(PDO $pdo, string $name): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM material_tags WHERE name = :name LIMIT 1');
    $stmt->execute([':name' => $name]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function updateSettings(PDO $pdo, string $legacyRoot): void
{
    $config = json_decode((string)$pdo->query("SELECT value FROM settings WHERE key = 'config'")->fetchColumn(), true) ?: [];
    $skin = json_decode((string)$pdo->query("SELECT value FROM settings WHERE key = 'skin'")->fetchColumn(), true) ?: [];

    $config['materialsTitle'] = 'DollMu,File';
    $config['materialsDescription'] = 'MUGENやドット絵制作に関する投稿ページを、タグと作者名で整理して保管します。';
    $config['materialsGroupParent'] = 'tag';
    $config['materialsDescriptionBannerEnabled'] = true;
    $config['materialsDescriptionBannerIntervalMs'] = 5000;
    $config['materialsDescriptionBanners'] = array_map(
        static fn(string $file): array => ['imageUrl' => './assets/' . $file, 'linkUrl' => '', 'alt' => 'DollMu,File'],
        ['title01.gif', 'title02.gif', 'title03.gif', 'title04.gif']
    );

    $bgPath = $legacyRoot . '/style/img/bg.gif';
    if (is_file($bgPath)) {
        $skin['materialsHeadingBackgroundImageUrl'] = dataUrl($bgPath);
    }

    upsertSetting($pdo, 'config', json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    upsertSetting($pdo, 'skin', json_encode($skin, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function upsertSetting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([':key' => $key, ':value' => $value]);
}

function collectMenuRecords(string $legacyRoot): array
{
    $html = (string)file_get_contents($legacyRoot . '/menu.html');
    $section = explode('<!--ここから作者順-->', $html)[0] ?? $html;
    $records = [];
    $category = 'その他';
    preg_match_all('/<li class="title">([\s\S]*?)<\/li>|<li><a\s+href="([^"]+)"[^>]*>([\s\S]*?)<\/a><\/li>/iu', $section, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        if (!empty($match[1])) {
            $category = cleanText($match[1]);
            continue;
        }
        $href = $match[2] ?? '';
        $title = cleanText($match[3] ?? '');
        if ($href === '' || $title === '' || !preg_match('/^toukou\//i', $href)) {
            continue;
        }
        $sourcePath = $legacyRoot . '/' . str_replace('/', DIRECTORY_SEPARATOR, $href);
        $articleHtml = is_file($sourcePath) ? (string)file_get_contents($sourcePath) : '';
        $records[] = [
            'href' => $href,
            'title' => $title,
            'category' => $category,
            'author' => authorFromHtml($articleHtml) ?: authorFromPath($href),
        ];
    }
    return $records;
}

function authorFromHtml(string $html): string
{
    if (preg_match('/<title>投稿者[:：]\s*([^<]+)<\/title>/u', $html, $match)) {
        return cleanText($match[1]);
    }
    if (preg_match('/投稿者[:：]\s*([^<\s]+)/u', $html, $match)) {
        return cleanText($match[1]);
    }
    return '';
}

function authorFromPath(string $href): string
{
    $parts = explode('/', $href);
    $folder = $parts[1] ?? 'unknown';
    return preg_replace('/^\d+/', '', $folder) ?: $folder;
}

function rewriteArticleHtml(string $html, string $sourceDir, string $legacyRoot): string
{
    $body = preg_match('/<body[^>]*>([\s\S]*?)<\/body>/iu', $html, $match) ? $match[1] : $html;
    $body = preg_replace('/<div id="PAGEHEADMENU"[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/iu', '', $body) ?? $body;
    $body = preg_replace('/<script[\s\S]*?<\/script>/iu', '', $body) ?? $body;
    $body = preg_replace('/<link[^>]+>/iu', '', $body) ?? $body;
    $body = preg_replace_callback('/\s(src|href)\s*=\s*["\']([^"\']+)["\']/iu', function (array $match) use ($sourceDir): string {
        $attr = $match[1];
        $url = $match[2];
        if (preg_match('/^(https?:|data:|mailto:|#)/i', $url)) {
            return $match[0];
        }
        $filePath = realpath($sourceDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $url));
        if (!$filePath || !is_file($filePath)) {
            return $match[0];
        }
        if (strtolower($attr) === 'src') {
            return ' ' . $attr . '="' . dataUrl($filePath) . '"';
        }
        return $match[0];
    }, $body) ?? $body;

    $css = '';
    foreach ([$legacyRoot . '/style/sinan.css', $sourceDir . '/style.css'] as $cssPath) {
        if (is_file($cssPath)) {
            $css .= "\n" . (string)file_get_contents($cssPath);
        }
    }
    $style = $css !== '' ? '<style>' . $css . '</style>' : '';
    return '<div class="legacy-document">' . $style . $body . '</div>';
}

function createArticleZip(string $archivePath, string $sourceDir, string $sourcePath): void
{
    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('zipを作成できません: ' . $archivePath);
    }
    $sourceReal = realpath($sourcePath);
    $dirReal = realpath($sourceDir);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $full = $file->getPathname();
        $relative = str_replace('\\', '/', substr($full, strlen($dirReal) + 1));
        if (realpath($full) === $sourceReal) {
            $zip->addFile($full, 'index.html');
        } else {
            $zip->addFile($full, $relative);
        }
    }
    if (!$zip->locateName('index.html') && $sourceReal) {
        $zip->addFile($sourceReal, 'index.html');
    }
    $zip->close();
}

function cleanText(string $value): string
{
    return html_entity_decode(trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function dataUrl(string $filePath): string
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = match ($extension) {
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'bmp' => 'image/bmp',
        'webp' => 'image/webp',
        'css' => 'text/css',
        default => 'application/octet-stream',
    };
    return 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($filePath));
}

function safeName(string $value): string
{
    $safe = preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $value) ?? 'document';
    return mb_substr($safe ?: 'document', 0, 80, 'UTF-8');
}
