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
        'tweet_like_count' => (int)($row['tweet_like_count'] ?? 0),
        'tweet_retweet_count' => (int)($row['tweet_retweet_count'] ?? 0),
        'tweet_comment_count' => (int)($row['tweet_comment_count'] ?? 0),
        'tweet_impression_count' => (int)($row['tweet_impression_count'] ?? 0),
    ];
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
