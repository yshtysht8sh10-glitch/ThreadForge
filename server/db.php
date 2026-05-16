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
            tweet_impression_count INTEGER NOT NULL DEFAULT 0,
            bluesky_uri TEXT,
            bluesky_cid TEXT,
            bluesky_url TEXT,
            bluesky_like_count INTEGER NOT NULL DEFAULT 0,
            bluesky_repost_count INTEGER NOT NULL DEFAULT 0,
            bluesky_quote_count INTEGER NOT NULL DEFAULT 0,
            mastodon_id TEXT,
            mastodon_url TEXT,
            mastodon_boost_count INTEGER NOT NULL DEFAULT 0,
            mastodon_fav_count INTEGER NOT NULL DEFAULT 0,
            misskey_id TEXT,
            misskey_url TEXT,
            misskey_fire_count INTEGER NOT NULL DEFAULT 0,
            misskey_eyes_count INTEGER NOT NULL DEFAULT 0,
            misskey_cry_count INTEGER NOT NULL DEFAULT 0,
            misskey_thinking_count INTEGER NOT NULL DEFAULT 0,
            misskey_party_count INTEGER NOT NULL DEFAULT 0,
            misskey_other_count INTEGER NOT NULL DEFAULT 0,
            user_id INTEGER,
            view_count INTEGER NOT NULL DEFAULT 0
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login_id TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            display_name TEXT NOT NULL DEFAULT "",
            post_password TEXT NOT NULL DEFAULT "",
            home_url TEXT,
            icon_path TEXT,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_sessions (
            token TEXT PRIMARY KEY,
            user_id INTEGER NOT NULL,
            created_at TEXT NOT NULL,
            expires_at TEXT NOT NULL
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
    ensureColumnExists($pdo, 'posts', 'bluesky_uri', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'bluesky_cid', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'bluesky_url', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'bluesky_like_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'bluesky_repost_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'bluesky_quote_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'mastodon_id', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'mastodon_url', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'mastodon_boost_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'mastodon_fav_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_id', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'misskey_url', 'TEXT');
    ensureColumnExists($pdo, 'posts', 'misskey_fire_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_eyes_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_cry_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_thinking_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_party_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'misskey_other_count', 'INTEGER NOT NULL DEFAULT 0');
    ensureColumnExists($pdo, 'posts', 'user_id', 'INTEGER');
    ensureColumnExists($pdo, 'posts', 'view_count', 'INTEGER NOT NULL DEFAULT 0');

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
    $post = [
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
        'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
        'user_icon_path' => publicStoragePath($row['user_icon_path'] ?? null),
        'view_count' => (int)($row['view_count'] ?? 0),
        'board_reactions' => buildBoardReactions($row),
        'social_links' => buildSocialLinks($row),
        'social_reactions' => buildSocialReactions($row),
    ];
    if (array_key_exists('reply_count', $row)) {
        $post['reply_count'] = (int)$row['reply_count'];
    }
    return $post;
}

function publicStoragePath(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    return '/storage/data/' . basename($path);
}

function buildBoardReactions(array $row): array
{
    return [
        'views' => (int)($row['view_count'] ?? 0),
        'eejanaika' => (int)($row['eejanaika_count'] ?? 0),
        'omigoto' => (int)($row['omigoto_count'] ?? 0),
        'goodjob' => (int)($row['goodjob_count'] ?? 0),
    ];
}

function buildSocialLinks(array $row): array
{
    return [
        'x' => $row['tweet_url'] ?? null,
        'bluesky' => $row['bluesky_url'] ?? null,
        'mastodon' => $row['mastodon_url'] ?? null,
        'misskey' => $row['misskey_url'] ?? null,
    ];
}

function buildSocialReactions(array $row): array
{
    return [
        'x' => [
            'likes' => (int)($row['tweet_like_count'] ?? 0),
            'reposts' => (int)($row['tweet_retweet_count'] ?? 0),
            'impressions' => (int)($row['tweet_impression_count'] ?? 0),
        ],
        'bluesky' => [
            'likes' => (int)($row['bluesky_like_count'] ?? 0),
            'reposts' => (int)($row['bluesky_repost_count'] ?? 0),
            'quotes' => (int)($row['bluesky_quote_count'] ?? 0),
        ],
        'mastodon' => [
            'boosts' => (int)($row['mastodon_boost_count'] ?? 0),
            'favs' => (int)($row['mastodon_fav_count'] ?? 0),
        ],
        'misskey' => [
            'fire' => (int)($row['misskey_fire_count'] ?? 0),
            'eyes' => (int)($row['misskey_eyes_count'] ?? 0),
            'cry' => (int)($row['misskey_cry_count'] ?? 0),
            'thinking' => (int)($row['misskey_thinking_count'] ?? 0),
            'party' => (int)($row['misskey_party_count'] ?? 0),
            'other' => (int)($row['misskey_other_count'] ?? 0),
        ],
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

function importLocalArchiveDirectory(PDO $pdo, string $archiveDir = 'data'): array
{
    $resolvedDir = resolveLocalArchiveDirectory($archiveDir);
    $files = glob($resolvedDir . DIRECTORY_SEPARATOR . 'LOG_*.cgi') ?: [];
    sort($files, SORT_NATURAL);

    $summary = [
        'success' => true,
        'message' => 'ローカルアーカイブログをインポートしました。',
        'source_dir' => $resolvedDir,
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
            importLocalArchiveLogFile($pdo, $file, $resolvedDir, $summary);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return $summary;
}

function resolveLocalArchiveDirectory(string $archiveDir): string
{
    $archiveDir = trim($archiveDir);
    if ($archiveDir === '') {
        $archiveDir = 'data';
    }

    $projectRoot = dirname(__DIR__);
    $candidate = preg_match('/^[A-Za-z]:[\/\\\\]|^[\/\\\\]/', $archiveDir)
        ? $archiveDir
        : $projectRoot . DIRECTORY_SEPARATOR . $archiveDir;
    $resolved = realpath($candidate);

    if ($resolved === false || !is_dir($resolved)) {
        throw new InvalidArgumentException('ローカルアーカイブログディレクトリが見つかりません。');
    }

    return $resolved;
}

function importLocalArchiveLogFile(PDO $pdo, string $file, string $archiveDir, array &$summary): void
{
    $raw = (string)file_get_contents($file);
    $raw = archiveToUtf8($raw);
    $lines = array_values(array_filter(preg_split('/\r\n|\n|\r/', $raw) ?: [], fn (string $line): bool => trim($line) !== ''));

    if (count($lines) === 0) {
        return;
    }

    $main = archiveFields($lines[0], 24);
    $name = archiveText($main[1] ?? '');
    $createdAt = archiveDateToTimestamp($main[2] ?? '');
    $title = archiveText($main[3] ?? '');
    $url = normalizeUrl($main[5] ?? null);
    $message = archiveMessageToText($main[6] ?? '');
    $filename = basename(trim((string)($main[10] ?? '')));
    $tweetOff = toBoolFlag($main[18] ?? false);
    $tweetUrl = normalizeUrl($main[19] ?? null);

    if ($name === '') {
        $name = 'imported';
    }
    if ($title === '') {
        $title = 'Imported thread';
    }

    $threadId = findImportedArchiveThread($pdo, $name, $title, $message, $createdAt);
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
            ':password_hash' => importedArchivePasswordHash(),
            ':created_at' => $createdAt,
            ':tweet_off' => $tweetOff ? 1 : 0,
            ':tweet_url' => $tweetUrl,
        ]);
        $threadId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :thread_id WHERE id = :id')->execute([':thread_id' => $threadId, ':id' => $threadId]);
        copyLocalArchiveImage($pdo, $archiveDir, $filename, $threadId, $summary);
        $summary['imported_threads']++;
    } else {
        $summary['skipped_threads']++;
    }

    for ($i = 1; $i < count($lines); $i++) {
        $reply = archiveFields($lines[$i], 10);
        $replyName = archiveText($reply[1] ?? '');
        $replyCreatedAt = archiveDateToTimestamp($reply[2] ?? '');
        $replyMessage = archiveMessageToText($reply[3] ?? '');
        $replyUrl = normalizeUrl($reply[7] ?? null);

        if ($replyName === '') {
            $replyName = 'imported';
        }
        if ($replyMessage === '') {
            continue;
        }
        if (importedArchiveReplyExists($pdo, $threadId, $replyName, $replyMessage, $replyCreatedAt)) {
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
            ':password_hash' => importedArchivePasswordHash(),
            ':created_at' => $replyCreatedAt,
        ]);
        $summary['imported_replies']++;
    }
}

function archiveFields(string $line, int $count): array
{
    return array_pad(explode("\t", rtrim($line, "\r\n")), $count, '');
}

function archiveToUtf8(string $value): string
{
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8,SJIS-win,EUC-JP,ISO-2022-JP');
    }
    return $value;
}

function archiveText(string $value): string
{
    return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function archiveMessageToText(string $value): string
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

function archiveDateToTimestamp(string $value): string
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

function importedArchivePasswordHash(): string
{
    return password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
}

function findImportedArchiveThread(PDO $pdo, string $name, string $title, string $message, string $createdAt): ?int
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

function importedArchiveReplyExists(PDO $pdo, int $threadId, string $name, string $message, string $createdAt): bool
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

function copyLocalArchiveImage(PDO $pdo, string $archiveDir, string $filename, int $postId, array &$summary): void
{
    if ($filename === '') {
        return;
    }

    $source = $archiveDir . DIRECTORY_SEPARATOR . basename($filename);
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
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
    return trim($value);
}

function normalizeLoginId(string $value): string
{
    $value = strtolower(trim($value));
    return preg_match('/\A[a-z0-9_.-]{3,40}\z/', $value) ? $value : '';
}

function authToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (is_string($header) && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
        return trim($matches[1]);
    }
    return trim((string)($_POST['auth_token'] ?? ''));
}

function optionalUser(PDO $pdo): ?array
{
    $token = authToken();
    if ($token === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT u.* FROM user_sessions s JOIN users u ON u.id = s.user_id
         WHERE s.token = :token AND s.expires_at > :now LIMIT 1'
    );
    $stmt->execute([':token' => $token, ':now' => currentTimestamp()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function requireUser(PDO $pdo): array
{
    $user = optionalUser($pdo);
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'ログインしてください。'], 401);
    }
    return $user;
}

function createUserSession(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))
        ->modify('+30 days')
        ->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('INSERT INTO user_sessions (token, user_id, created_at, expires_at) VALUES (:token, :user_id, :created_at, :expires_at)');
    $stmt->execute([
        ':token' => $token,
        ':user_id' => $userId,
        ':created_at' => currentTimestamp(),
        ':expires_at' => $expires,
    ]);
    return $token;
}

function findUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function findUserByLoginId(PDO $pdo, string $loginId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE login_id = :login_id LIMIT 1');
    $stmt->execute([':login_id' => $loginId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function buildUser(?array $user): ?array
{
    if ($user === null) {
        return null;
    }
    return [
        'id' => (int)$user['id'],
        'login_id' => (string)$user['login_id'],
        'display_name' => (string)($user['display_name'] ?? ''),
        'post_password' => (string)($user['post_password'] ?? ''),
        'home_url' => $user['home_url'] ?? null,
        'icon_path' => publicStoragePath($user['icon_path'] ?? null),
    ];
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

function buildTweetText(string $name, string $title, string $message, ?string $sourceUrl = null, ?string $hashtags = null): string
{
    return buildSocialPostText('x', $name, $title, $message, $sourceUrl, $hashtags);
}

function buildSocialPostText(string $platform, string $name, string $title, string $message, ?string $sourceUrl = null, ?string $hashtags = null): string
{
    $tweetMessage = preg_split('/_TWEND_/', $message, 2)[0] ?? '';
    $tagLine = trim((string)($hashtags ?? '#ドット絵 #pixelart'));
    $text = '[DT000000：' . $title . ']' . "\n"
        . '作者：' . $name . "\n\n"
        . trim($tweetMessage) . "\n\n";

    if ($sourceUrl !== null && $sourceUrl !== '') {
        $text .= '最新はこちら ' . $sourceUrl . "\n";
    }

    if ($tagLine !== '') {
        $text .= $tagLine;
    }

    return trimToSocialPostLength($text, socialPostLimit($platform), $platform === 'x');
}

function countTweetLength(string $text): int
{
    preg_match_all('/https?:\/\/[^\s]+/u', $text, $matches);
    $withoutUrls = preg_replace('/https?:\/\/[^\s]+/u', '', $text) ?? $text;
    $chars = preg_split('//u', $withoutUrls, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $length = count($matches[0] ?? []) * 23;

    foreach ($chars as $char) {
        $length += tweetCharacterWeight($char);
    }

    return $length;
}

function trimToTweetLength(string $text): string
{
    return trimToSocialPostLength($text, 280, true);
}

function countSocialPostLength(string $text, bool $tweetWeighted = false): int
{
    return $tweetWeighted ? countTweetLength($text) : mb_strlen($text, 'UTF-8');
}

function trimToSocialPostLength(string $text, int $limit, bool $tweetWeighted = false): string
{
    if (countSocialPostLength($text, $tweetWeighted) <= $limit) {
        return $text;
    }

    $ellipsis = '..';
    $marker = "\n\n最新はこちら ";
    $sourcePos = mb_strpos($text, $marker, 0, 'UTF-8');
    $tagPos = mb_strrpos($text, "\n#", 0, 'UTF-8');
    $tailPos = $sourcePos !== false ? $sourcePos : ($tagPos !== false ? $tagPos : false);
    $body = $tailPos === false ? $text : mb_substr($text, 0, $tailPos, 'UTF-8');
    $tail = $tailPos === false ? '' : mb_substr($text, $tailPos, null, 'UTF-8');

    while ($body !== '' && countSocialPostLength($body . $ellipsis . $tail, $tweetWeighted) > $limit) {
        $body = mb_substr($body, 0, mb_strlen($body, 'UTF-8') - 1, 'UTF-8');
    }

    while ($tail !== '' && countSocialPostLength(rtrim($body) . $ellipsis . $tail, $tweetWeighted) > $limit) {
        $tail = mb_substr($tail, 0, mb_strlen($tail, 'UTF-8') - 1, 'UTF-8');
    }

    return rtrim($body) . $ellipsis . $tail;
}

function socialPostLimit(string $platform): int
{
    return [
        'x' => 280,
        'bluesky' => 300,
        'mastodon' => 500,
        'misskey' => 3000,
    ][$platform] ?? 500;
}

function tweetCharacterWeight(string $char): int
{
    if ($char === "\r") {
        return 0;
    }
    return preg_match('/[\x{0000}-\x{10ff}\x{2000}-\x{200d}\x{2010}-\x{201f}\x{2032}-\x{2037}]/u', $char) === 1 ? 1 : 2;
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

    $extension = imageExtensionFromUpload($file);
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

function saveUploadedUserIcon(array $file, int $userId): ?string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $extension = imageExtensionFromUpload($file);
    if ($extension === null) {
        return null;
    }

    $destination = STORAGE_DIR . '/user_' . $userId . '.' . $extension;
    if (file_exists($destination) && archiveExistingImage($destination) === null) {
        return null;
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

function imageExtensionFromUpload(array $file): ?string
{
    $tmpName = $file['tmp_name'] ?? '';
    if (is_string($tmpName) && $tmpName !== '' && class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpName);
        if (is_string($mimeType)) {
            return imageExtensionFromMime($mimeType);
        }
    }

    return imageExtensionFromMime((string)($file['type'] ?? ''));
}

function jsonResponse(array $data, int $status = 200): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
