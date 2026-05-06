<?php

declare(strict_types=1);

if (!defined('DB_FILE')) {
    define('DB_FILE', getenv('DOTEITA_DB_FILE') ?: __DIR__ . '/database.sqlite');
}

if (!defined('STORAGE_DIR')) {
    define('STORAGE_DIR', getenv('DOTEITA_STORAGE_DIR') ?: __DIR__ . '/storage/data');
}

function getConnection(): PDO
{
    if (!file_exists(DB_FILE)) {
        touch(DB_FILE);
    }

    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    initializeDatabase($pdo);
    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_id INTEGER NOT NULL,
            parent_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            url TEXT,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            image_path TEXT,
            password_hash TEXT,
            created_at TEXT NOT NULL,
            deleted_at TEXT,
            gdgd INTEGER NOT NULL DEFAULT 0,
            tweet_off INTEGER NOT NULL DEFAULT 0,
            tweet_text TEXT,
            tweet_url TEXT,
            tweet_like_count INTEGER NOT NULL DEFAULT 0,
            tweet_retweet_count INTEGER NOT NULL DEFAULT 0,
            tweet_comment_count INTEGER NOT NULL DEFAULT 0,
            tweet_impression_count INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )'
    );

    ensureColumnExists($pdo, 'posts', 'deleted_at', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'url', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'gdgd', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'tweet_off', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'tweet_text', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'tweet_url', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'tweet_like_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'tweet_retweet_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'tweet_comment_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'tweet_impression_count', 'INTEGER NOT NULL DEFAULT 0');

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }
}

function ensureColumnExists(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $row) {
        if (($row['name'] ?? '') === $column) {
            return;
        }
    }

    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function buildPost(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'thread_id' => (int)$row['thread_id'],
        'parent_id' => (int)$row['parent_id'],
        'name' => $row['name'],
        'url' => $row['url'] ?? null,
        'title' => $row['title'],
        'message' => $row['message'],
        'image_path' => $row['image_path'] ? '/storage/data/' . basename($row['image_path']) : null,
        'created_at' => $row['created_at'],
        'deleted_at' => $row['deleted_at'] ?? null,
        'gdgd' => (bool)($row['gdgd'] ?? false),
        'tweet_off' => (bool)($row['tweet_off'] ?? false),
        'tweet_text' => $row['tweet_text'] ?? null,
        'tweet_url' => $row['tweet_url'] ?? null,
    ];
}

function withDisplayNo(PDO $pdo, array $post, int $threadId): array
{
    $post['display_no'] = threadDisplayNo($pdo, $threadId);
    return $post;
}

function withReplyNo(PDO $pdo, array $post, int $threadId, int $replyId): array
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE thread_id = :thread_id AND id != thread_id AND id <= :reply_id');
    $stmt->execute([':thread_id' => $threadId, ':reply_id' => $replyId]);
    $post['reply_no'] = (int)$stmt->fetchColumn();
    return $post;
}

function threadDisplayNo(PDO $pdo, int $threadId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE parent_id = 0 AND id <= :id');
    $stmt->execute([':id' => $threadId]);
    return (int)$stmt->fetchColumn();
}

function buildDeletedPost(PDO $pdo, array $row): array
{
    $post = buildPost($row);
    $threadId = (int)$row['thread_id'];
    $post['display_no'] = threadDisplayNo($pdo, $threadId);

    if ((int)$row['parent_id'] !== 0 && (int)$row['id'] !== $threadId) {
        $post = withReplyNo($pdo, $post, $threadId, (int)$row['id']);
    }

    return $post;
}

function importLegacyBbsnoteDirectory(PDO $pdo, string $legacyDir = 'data'): array
{
    $resolvedDir = resolveLegacyBbsnoteDirectory($legacyDir);
    $files = glob($resolvedDir . DIRECTORY_SEPARATOR . 'LOG_*.cgi') ?: [];
    sort($files, SORT_NATURAL);

    $summary = [
        'success' => true,
        'message' => '旧BBSnoteログをインポートしました。',
        'legacy_dir' => $resolvedDir,
        'imported_threads' => 0,
        'imported_replies' => 0,
        'skipped_threads' => 0,
        'skipped_replies' => 0,
        'missing_images' => [],
        'files' => count($files),
    ];

    $pdo->beginTransaction();
    try {
        foreach ($files as $file) {
            importLegacyBbsnoteLogFile($pdo, $file, $resolvedDir, $summary);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return $summary;
}

function resolveLegacyBbsnoteDirectory(string $legacyDir): string
{
    $legacyDir = trim($legacyDir);
    if ($legacyDir === '') {
        $legacyDir = 'data';
    }

    $projectRoot = dirname(__DIR__);
    $candidate = preg_match('/^[A-Za-z]:[\/\\\\]|^[\/\\\\]/', $legacyDir)
        ? $legacyDir
        : $projectRoot . DIRECTORY_SEPARATOR . $legacyDir;
    $resolved = realpath($candidate);

    if ($resolved === false || !is_dir($resolved)) {
        throw new InvalidArgumentException('旧BBSnoteログディレクトリが見つかりません。');
    }

    return $resolved;
}

function importLegacyBbsnoteLogFile(PDO $pdo, string $file, string $legacyDir, array &$summary): void
{
    $raw = (string)file_get_contents($file);
    $raw = legacyToUtf8($raw);
    $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $raw) ?: [], fn (string $line): bool => trim($line) !== ''));

    if (count($lines) === 0) {
        return;
    }

    $main = legacyFields($lines[0], 24);
    $name = legacyText($main[1] ?? '');
    $createdAt = legacyDateToTimestamp($main[2] ?? '');
    $title = legacyText($main[3] ?? '');
    $url = normalizeUrl($main[5] ?? null);
    $message = legacyMessageToText($main[6] ?? '');
    $filename = basename(trim((string)($main[10] ?? '')));
    $tweetOff = toBoolFlag($main[18] ?? false);
    $tweetUrl = normalizeUrl($main[19] ?? null);

    if ($name === '') {
        $name = 'imported';
    }
    if ($title === '') {
        $title = 'Imported thread';
    }

    $threadId = findLegacyThread($pdo, $name, $title, $message, $createdAt);
    if ($threadId === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO posts (
                thread_id, parent_id, name, url, title, message, image_path, password_hash, created_at, deleted_at, gdgd,
                tweet_off, tweet_text, tweet_url, tweet_like_count, tweet_retweet_count, tweet_comment_count, tweet_impression_count
            ) VALUES (
                0, 0, :name, :url, :title, :message, null, :password_hash, :created_at, null, 0,
                :tweet_off, null, :tweet_url, 0, 0, 0, 0
            )'
        );
        $stmt->execute([
            ':name' => $name,
            ':url' => $url,
            ':title' => $title,
            ':message' => $message,
            ':password_hash' => legacyImportedPasswordHash(),
            ':created_at' => $createdAt,
            ':tweet_off' => $tweetOff ? 1 : 0,
            ':tweet_url' => $tweetUrl,
        ]);
        $threadId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :thread_id WHERE id = :id')->execute([':thread_id' => $threadId, ':id' => $threadId]);
        copyLegacyBbsnoteImage($pdo, $legacyDir, $filename, $threadId, $summary);
        $summary['imported_threads']++;
    } else {
        $summary['skipped_threads']++;
    }

    for ($i = 1; $i < count($lines); $i++) {
        $reply = legacyFields($lines[$i], 10);
        $replyName = legacyText($reply[1] ?? '');
        $replyCreatedAt = legacyDateToTimestamp($reply[2] ?? '');
        $replyMessage = legacyMessageToText($reply[3] ?? '');
        $replyUrl = normalizeUrl($reply[7] ?? null);

        if ($replyName === '') {
            $replyName = 'imported';
        }
        if ($replyMessage === '') {
            continue;
        }
        if (legacyReplyExists($pdo, $threadId, $replyName, $replyMessage, $replyCreatedAt)) {
            $summary['skipped_replies']++;
            continue;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO posts (
                thread_id, parent_id, name, url, title, message, image_path, password_hash, created_at, deleted_at, gdgd,
                tweet_off, tweet_text, tweet_url, tweet_like_count, tweet_retweet_count, tweet_comment_count, tweet_impression_count
            ) VALUES (
                :thread_id, :parent_id, :name, :url, :title, :message, null, :password_hash, :created_at, null, 0,
                1, null, null, 0, 0, 0, 0
            )'
        );
        $stmt->execute([
            ':thread_id' => $threadId,
            ':parent_id' => $threadId,
            ':name' => $replyName,
            ':url' => $replyUrl,
            ':title' => 'Re: ' . $title,
            ':message' => $replyMessage,
            ':password_hash' => legacyImportedPasswordHash(),
            ':created_at' => $replyCreatedAt,
        ]);
        $summary['imported_replies']++;
    }
}

function legacyFields(string $line, int $count): array
{
    return array_pad(explode("\t", rtrim($line, "\r\n")), $count, '');
}

function legacyToUtf8(string $value): string
{
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8,SJIS-win,EUC-JP,ISO-2022-JP');
    }
    return $value;
}

function legacyText(string $value): string
{
    return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function legacyMessageToText(string $value): string
{
    $value = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $value) ?? $value;
    $value = preg_replace('/<\s*p\s*\/?\s*>/i', "\n\n", $value) ?? $value;
    $value = preg_replace('/<\s*\/p\s*>/i', "\n\n", $value) ?? $value;
    $value = strip_tags($value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
    return trim($value);
}

function legacyDateToTimestamp(string $value): string
{
    if (preg_match('/(\d{4})\/(\d{1,2})\/(\d{1,2}).*?(\d{1,2}):(\d{1,2}):(\d{1,2})/', $value, $matches)) {
        return sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            (int)$matches[1],
            (int)$matches[2],
            (int)$matches[3],
            (int)$matches[4],
            (int)$matches[5],
            (int)$matches[6]
        );
    }

    return currentTimestamp();
}

function legacyImportedPasswordHash(): string
{
    return password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
}

function findLegacyThread(PDO $pdo, string $name, string $title, string $message, string $createdAt): ?int
{
    $stmt = $pdo->prepare(
        'SELECT id FROM posts
         WHERE parent_id = 0 AND name = :name AND title = :title AND message = :message AND created_at = :created_at
         LIMIT 1'
    );
    $stmt->execute([
        ':name' => $name,
        ':title' => $title,
        ':message' => $message,
        ':created_at' => $createdAt,
    ]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

function legacyReplyExists(PDO $pdo, int $threadId, string $name, string $message, string $createdAt): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM posts
         WHERE thread_id = :thread_id AND parent_id != 0 AND name = :name AND message = :message AND created_at = :created_at'
    );
    $stmt->execute([
        ':thread_id' => $threadId,
        ':name' => $name,
        ':message' => $message,
        ':created_at' => $createdAt,
    ]);
    return (int)$stmt->fetchColumn() > 0;
}

function copyLegacyBbsnoteImage(PDO $pdo, string $legacyDir, string $filename, int $postId, array &$summary): void
{
    if ($filename === '') {
        return;
    }

    $source = $legacyDir . DIRECTORY_SEPARATOR . basename($filename);
    if (!is_file($source)) {
        $summary['missing_images'][] = $filename;
        return;
    }

    $extension = strtolower((string)pathinfo($source, PATHINFO_EXTENSION));
    if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif'], true)) {
        $summary['missing_images'][] = $filename;
        return;
    }
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }

    $destination = STORAGE_DIR . DIRECTORY_SEPARATOR . $postId . '.' . $extension;
    if (!copy($source, $destination)) {
        $summary['missing_images'][] = $filename;
        return;
    }
    $pdo->prepare('UPDATE posts SET image_path = :image_path WHERE id = :id')->execute([
        ':image_path' => $destination,
        ':id' => $postId,
    ]);
}

function normalizeString(string $value): string
{
    return trim($value);
}

function findPostById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function findActivePostById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function currentTimestamp(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d H:i:s');
}

function resolveAction(array $query, array $post): ?string
{
    return $query['action'] ?? $post['action'] ?? null;
}

function normalizeUrl(?string $value): ?string
{
    $url = trim((string)$value);
    if ($url === '' || $url === 'http://' || $url === 'https://') {
        return null;
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

function toBoolFlag($value): bool
{
    return $value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true';
}

function allowsImageUpload(int $threadId, int $parentId): bool
{
    return $threadId === 0 && $parentId === 0;
}

function normalizeCount($value): int
{
    $count = filter_var($value, FILTER_VALIDATE_INT);
    return $count === false || $count < 0 ? 0 : $count;
}

function buildTweetText(string $name, string $title, string $message, ?string $sourceUrl = null): string
{
    $tweetMessage = preg_split('/_TWEND_/', $message, 2)[0] ?? '';
    $text = '[DT000000：' . $title . ']' . "\n"
        . '作者：' . $name . "\n\n"
        . trim($tweetMessage) . "\n\n";

    if ($sourceUrl !== null && $sourceUrl !== '') {
        $text .= '元：' . $sourceUrl . "\n";
    }

    $text .= '#ドット絵 #pixelart';

    return trimToTweetLength($text);
}

function countTweetLength(string $text): int
{
    $withoutUrls = preg_replace('/https?:\/\/[^\s]+/u', '', $text) ?? $text;
    preg_match_all('/https?:\/\/[^\s]+/u', $text, $matches);
    $withoutBreaks = str_replace(["\r\n", "\r", "\n"], '', $withoutUrls);

    return mb_strlen($withoutBreaks, 'UTF-8') + (count($matches[0] ?? []) * 23);
}

function trimToTweetLength(string $text): string
{
    if (countTweetLength($text) <= 280) {
        return $text;
    }

    $marker = "\n\n元：";
    $sourcePos = mb_strpos($text, $marker, 0, 'UTF-8');
    $body = $sourcePos === false ? $text : mb_substr($text, 0, $sourcePos, 'UTF-8');
    $tail = $sourcePos === false ? '' : mb_substr($text, $sourcePos, null, 'UTF-8');

    while ($body !== '' && countTweetLength($body . '...' . $tail) > 280) {
        $body = mb_substr($body, 0, mb_strlen($body, 'UTF-8') - 1, 'UTF-8');
    }

    return rtrim($body) . '...' . $tail;
}

function hasRecentDuplicatePost(PDO $pdo, string $name, string $title, string $message, int $windowSeconds = 60): bool
{
    $threshold = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))
        ->modify('-' . $windowSeconds . ' seconds')
        ->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM posts
         WHERE deleted_at IS NULL
           AND name = :name
           AND title = :title
           AND message = :message
           AND created_at >= :threshold'
    );
    $stmt->execute([
        ':name' => $name,
        ':title' => $title,
        ':message' => $message,
        ':threshold' => $threshold,
    ]);

    return (int)$stmt->fetchColumn() > 0;
}

function deleteImage(?string $path): void
{
    if ($path && file_exists($path)) {
        @unlink($path);
    }
}

function saveUploadedImage(array $file, int $postId): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $extension = imageExtensionFromMime($file['type'] ?? '');
    if ($extension === null) {
        return null;
    }

    $destination = STORAGE_DIR . '/' . $postId . '.' . $extension;
    if (file_exists($destination)) {
        if (archiveExistingImage($destination) === null) {
            return null;
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return $destination;
}

function archiveExistingImage(string $path): ?string
{
    if (!file_exists($path)) {
        return null;
    }

    $directory = dirname($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $filename = pathinfo($path, PATHINFO_FILENAME);
    $timestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('YmdHis');
    $suffix = $extension === '' ? '' : '.' . $extension;
    $archivePath = $directory . '/' . $filename . '_' . $timestamp . $suffix;
    $counter = 1;

    while (file_exists($archivePath)) {
        $archivePath = $directory . '/' . $filename . '_' . $timestamp . '_' . $counter . $suffix;
        $counter++;
    }

    if (!rename($path, $archivePath)) {
        return null;
    }

    return $archivePath;
}

function imageExtensionFromMime(string $mimeType): ?string
{
    $extensions = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
    ];

    return $extensions[$mimeType] ?? null;
}

function jsonResponse(array $data, int $status = 200): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
