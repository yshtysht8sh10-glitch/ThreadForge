<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = resolveAction($_GET, $_POST);
$pdo = getConnection();

switch ($action) {
    case 'listThreads':
        listThreads($pdo);
        break;
    case 'getThread':
        getThread($pdo);
        break;
    case 'getPost':
        getPost($pdo);
        break;
    case 'search':
        searchPosts($pdo);
        break;
    case 'rss':
        rssFeed($pdo);
        break;
    case 'version':
        versionInfo();
        break;
    case 'publicSettings':
        publicSettings($pdo);
        break;
    case 'createPost':
        createPost($pdo);
        break;
    case 'updatePost':
        updatePost($pdo);
        break;
    case 'deletePost':
        deletePost($pdo);
        break;
    case 'listDeletedPosts':
        listDeletedPosts($pdo);
        break;
    case 'restorePost':
        restorePost($pdo);
        break;
    case 'adminDeletePosts':
        adminDeletePosts($pdo);
        break;
    case 'adminCheckIntegrity':
        adminCheckIntegrity($pdo);
        break;
    case 'exportBackup':
        exportBackup($pdo);
        break;
    case 'importBackup':
        importBackup($pdo);
        break;
    case 'importLegacyBbsnote':
        importLegacyBbsnote($pdo);
        break;
    case 'getSettings':
        getSettings($pdo);
        break;
    case 'updateSettings':
        updateSettings($pdo);
        break;
    case 'changeAdminPassword':
        changeAdminPassword($pdo);
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'アクションが無効です。'], 400);
}

function listThreads(PDO $pdo): void
{
    [$limit, $offset] = paginationParams();
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE parent_id = 0 AND deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(
        fn (array $row): array => buildThreadSummary($pdo, $row, 10),
        $rows
    );
    jsonResponse($result);
}

function buildThreadSummary(PDO $pdo, array $thread, int $replyLimit): array
{
    $post = buildPost($thread);
    $post['display_no'] = threadDisplayNo($pdo, (int)$thread['id']);

    $replyStmt = $pdo->prepare('SELECT * FROM posts WHERE thread_id = :thread_id AND id != :thread_id AND deleted_at IS NULL ORDER BY created_at ASC LIMIT :limit');
    $replyStmt->bindValue(':thread_id', (int)$thread['id'], PDO::PARAM_INT);
    $replyStmt->bindValue(':limit', $replyLimit, PDO::PARAM_INT);
    $replyStmt->execute();

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE thread_id = :thread_id AND id != :thread_id AND deleted_at IS NULL');
    $countStmt->execute([':thread_id' => (int)$thread['id']]);

    $post['replies'] = array_map(
        fn (array $reply): array => withReplyNo($pdo, buildPost($reply), (int)$thread['id'], (int)$reply['id']),
        $replyStmt->fetchAll(PDO::FETCH_ASSOC)
    );
    $post['reply_count'] = (int)$countStmt->fetchColumn();

    return $post;
}

function getThread(PDO $pdo): void
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => 'スレッドIDが不正です。'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([':id' => $id]);
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        jsonResponse(['success' => false, 'message' => 'スレッドが見つかりません。'], 404);
    }

    $replyStmt = $pdo->prepare('SELECT * FROM posts WHERE thread_id = :thread_id AND id != :thread_id AND deleted_at IS NULL ORDER BY created_at ASC');
    $replyStmt->execute([':thread_id' => $id]);
    $replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'thread' => withDisplayNo($pdo, buildPost($thread), (int)$thread['id']),
        'replies' => array_map(
            fn (array $reply): array => withReplyNo($pdo, buildPost($reply), $id, (int)$reply['id']),
            $replies
        ),
    ]);
}

function searchPosts(PDO $pdo): void
{
    $q = trim($_GET['q'] ?? '');
    if ($q === '') {
        jsonResponse([]);
    }

    [$limit, $offset] = paginationParams();
    $pattern = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
    $scope = $_GET['scope'] ?? 'all';
    $where = match ($scope) {
        'title' => 'title LIKE :q ESCAPE "\\"',
        'message' => 'message LIKE :q ESCAPE "\\"',
        'name' => 'name LIKE :q ESCAPE "\\"',
        default => '(title LIKE :q ESCAPE "\\" OR message LIKE :q ESCAPE "\\" OR name LIKE :q ESCAPE "\\")',
    };
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE deleted_at IS NULL AND ' . $where . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':q', $pattern, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(array_map('buildPost', $rows));
}

function rssFeed(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE parent_id = 0 AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 30');
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $baseUrl = rtrim((string)($_GET['base_url'] ?? ''), '/');

    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<rss version="2.0"><channel>';
    echo '<title>ThreadForge</title>';
    echo '<link>' . htmlspecialchars($baseUrl !== '' ? $baseUrl : '/', ENT_XML1) . '</link>';
    echo '<description>ThreadForge latest posts</description>';

    foreach ($posts as $row) {
        $post = buildPost($row);
        $link = ($baseUrl !== '' ? $baseUrl : '') . '/thread/' . $post['id'];
        echo '<item>';
        echo '<title>' . htmlspecialchars($post['title'], ENT_XML1) . '</title>';
        echo '<link>' . htmlspecialchars($link, ENT_XML1) . '</link>';
        echo '<guid>' . htmlspecialchars($link, ENT_XML1) . '</guid>';
        echo '<description>' . htmlspecialchars($post['message'], ENT_XML1) . '</description>';
        echo '<pubDate>' . htmlspecialchars(date(DATE_RSS, strtotime($post['created_at'])), ENT_XML1) . '</pubDate>';
        echo '</item>';
    }

    echo '</channel></rss>';
    exit;
}

function versionInfo(): void
{
    jsonResponse([
        'name' => 'ThreadForge',
        'version' => appVersion(),
    ]);
}

function appVersion(): string
{
    $versionFile = dirname(__DIR__) . '/VERSION';
    if (is_file($versionFile)) {
        return trim((string)file_get_contents($versionFile));
    }
    return '0.0.0-dev';
}

function createPost(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $tweetEnabled = toBoolFlag($config['tweetEnabled'] ?? true);
    $gdgdEnabled = toBoolFlag($config['gdgdEnabled'] ?? true);
    $name = normalizeString($_POST['name'] ?? '');
    $url = normalizeUrl($_POST['url'] ?? null);
    $title = normalizeString($_POST['title'] ?? '');
    $message = normalizeString($_POST['message'] ?? '');
    $password = $_POST['password'] ?? '';
    $threadId = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT) ?: 0;
    $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: 0;
    $isReply = $threadId !== 0 || $parentId !== 0;
    $gdgd = $isReply || !$gdgdEnabled ? false : toBoolFlag($_POST['gdgd'] ?? $_POST['gdgd_post'] ?? false);
    $tweetOff = $isReply || !$tweetEnabled ? true : toBoolFlag($_POST['tweet_off'] ?? $_POST['TweetOFF'] ?? false);
    $tweetUrl = null;

    if ($name === '' || $title === '' || $message === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => '名前・タイトル・本文・パスワードは必須です。'], 400);
    }

    if (hasRecentDuplicatePost($pdo, $name, $title, $message)) {
        jsonResponse(['success' => false, 'message' => '同じ内容の連続投稿は少し時間を空けてください。'], 429);
    }

    $createdAt = currentTimestamp();
    $sourceUrl = $_POST['source_url'] ?? null;
    $tweetText = $tweetOff ? null : buildTweetText($name, $title, $message, is_string($sourceUrl) ? normalizeUrl($sourceUrl) : null);

    if ($threadId === 0) {
        $parentId = 0;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO posts (
            thread_id, parent_id, name, url, title, message, image_path, password_hash, created_at, gdgd,
            tweet_off, tweet_text, tweet_url, tweet_like_count, tweet_retweet_count, tweet_comment_count, tweet_impression_count
        ) VALUES (
            :thread_id, :parent_id, :name, :url, :title, :message, :image_path, :password_hash, :created_at, :gdgd,
            :tweet_off, :tweet_text, :tweet_url, :tweet_like_count, :tweet_retweet_count, :tweet_comment_count, :tweet_impression_count
        )'
    );
    $stmt->execute([
        ':thread_id' => $threadId === 0 ? 0 : $threadId,
        ':parent_id' => $parentId,
        ':name' => $name,
        ':url' => $url,
        ':title' => $title,
        ':message' => $message,
        ':image_path' => null,
        ':password_hash' => $hash,
        ':created_at' => $createdAt,
        ':gdgd' => $gdgd ? 1 : 0,
        ':tweet_off' => $tweetOff ? 1 : 0,
        ':tweet_text' => $tweetText,
        ':tweet_url' => null,
        ':tweet_like_count' => 0,
        ':tweet_retweet_count' => 0,
        ':tweet_comment_count' => 0,
        ':tweet_impression_count' => 0,
    ]);

    $insertedId = (int)$pdo->lastInsertId();

    if ($threadId === 0) {
        $update = $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id');
        $update->execute([':id' => $insertedId]);
    }

    $imagePath = null;
    if (allowsImageUpload($threadId, $parentId) && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $imagePath = saveUploadedImage($_FILES['file'], $insertedId);
        if ($imagePath === null) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => '画像のアップロードに失敗しました。'], 400);
        }

        $updateImage = $pdo->prepare('UPDATE posts SET image_path = :image_path WHERE id = :id');
        $updateImage->execute([
            ':id' => $insertedId,
            ':image_path' => $imagePath,
        ]);
    }

    if ($tweetText !== null) {
        $tweetText = fillTweetPostId($tweetText, $insertedId);
        $updateTweetText = $pdo->prepare('UPDATE posts SET tweet_text = :tweet_text WHERE id = :id');
        $updateTweetText->execute([
            ':id' => $insertedId,
            ':tweet_text' => $tweetText,
        ]);
    }

    $pdo->commit();

    $responseMessage = '投稿が完了しました。';
    if (!$tweetOff && $tweetText !== null) {
        $tweetResult = publishTweetFromSettings($settings, $tweetText, $imagePath);
        if ($tweetResult['success']) {
            $tweetUrl = $tweetResult['url'];
            $updateTweetUrl = $pdo->prepare('UPDATE posts SET tweet_url = :tweet_url WHERE id = :id');
            $updateTweetUrl->execute([
                ':id' => $insertedId,
                ':tweet_url' => $tweetUrl,
            ]);
            $responseMessage .= ' Tweetしました。';
        } else {
            $responseMessage .= ' Tweetは失敗しました: ' . $tweetResult['message'];
        }
    }

    jsonResponse(['success' => true, 'message' => $responseMessage, 'tweet_url' => $tweetUrl]);
}

function fillTweetPostId(string $tweetText, int $postId): string
{
    return preg_replace('/000000/', str_pad((string)$postId, 6, '0', STR_PAD_LEFT), $tweetText, 1) ?? $tweetText;
}

function publishTweetFromSettings(array $settings, string $text, ?string $imagePath): array
{
    $config = $settings['config'] ?? [];
    $consumerKey = trim((string)($config['tweetConsumerKey'] ?? ''));
    $consumerSecret = trim((string)($config['tweetConsumerSecret'] ?? ''));
    $accessToken = trim((string)($config['tweetAccessToken'] ?? ''));
    $accessTokenSecret = trim((string)($config['tweetAccessTokenSecret'] ?? ''));
    $baseUrl = trim((string)($config['tweetBaseUrl'] ?? 'https://twitter.com/MUGEN87112020/status/'));

    if ($consumerKey === '' || $consumerSecret === '' || $accessToken === '' || $accessTokenSecret === '') {
        return ['success' => false, 'message' => 'Twitter API設定が未入力です。', 'url' => null];
    }

    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL拡張が有効ではありません。', 'url' => null];
    }

    $mediaIds = [];
    if ($imagePath !== null && is_file($imagePath)) {
        $upload = twitterUploadMedia($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $imagePath);
        if (!$upload['success']) {
            return ['success' => false, 'message' => $upload['message'], 'url' => null];
        }
        $mediaIds[] = $upload['media_id'];
    }

    $tweet = twitterCreateTweet($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $text, $mediaIds);
    if (!$tweet['success']) {
        return ['success' => false, 'message' => $tweet['message'], 'url' => null];
    }

    if ($baseUrl === '') {
        $baseUrl = 'https://twitter.com/i/web/status/';
    }
    if (substr($baseUrl, -1) !== '/') {
        $baseUrl .= '/';
    }

    return ['success' => true, 'message' => 'Tweetしました。', 'url' => $baseUrl . $tweet['tweet_id']];
}

function twitterUploadMedia(
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    string $imagePath
): array {
    $url = 'https://upload.twitter.com/1.1/media/upload.json';
    $response = twitterRequest(
        'POST',
        $url,
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        ['media' => new CURLFile($imagePath)],
        false
    );

    if (!$response['success']) {
        return $response;
    }

    $mediaId = $response['body']['media_id_string'] ?? $response['body']['media_id'] ?? null;
    if ($mediaId === null || $mediaId === '') {
        return ['success' => false, 'message' => 'Twitterの画像アップロード応答にmedia_idがありません。'];
    }

    return ['success' => true, 'media_id' => (string)$mediaId];
}

function twitterCreateTweet(
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    string $text,
    array $mediaIds
): array {
    $payload = ['text' => $text];
    if (count($mediaIds) > 0) {
        $payload['media'] = ['media_ids' => $mediaIds];
    }

    $response = twitterRequest(
        'POST',
        'https://api.twitter.com/2/tweets',
        $consumerKey,
        $consumerSecret,
        $accessToken,
        $accessTokenSecret,
        $payload,
        true
    );

    if (!$response['success']) {
        return $response;
    }

    $tweetId = $response['body']['data']['id'] ?? null;
    if ($tweetId === null || $tweetId === '') {
        return ['success' => false, 'message' => 'Twitterの投稿応答にTweet IDがありません。'];
    }

    return ['success' => true, 'tweet_id' => (string)$tweetId];
}

function twitterRequest(
    string $method,
    string $url,
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret,
    array $payload,
    bool $jsonBody
): array {
    $headers = [
        'Authorization: ' . twitterOAuthHeader($method, $url, $consumerKey, $consumerSecret, $accessToken, $accessTokenSecret),
    ];
    $body = $payload;
    if ($jsonBody) {
        $headers[] = 'Content-Type: application/json';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($raw === false) {
        return ['success' => false, 'message' => 'Twitter API通信に失敗しました: ' . $error];
    }

    $decoded = json_decode((string)$raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string)$raw;
        return ['success' => false, 'message' => 'Twitter APIがエラーを返しました。HTTP ' . $status . ' ' . $detail];
    }

    if (!is_array($decoded)) {
        return ['success' => false, 'message' => 'Twitter API応答がJSONではありません。'];
    }

    return ['success' => true, 'body' => $decoded];
}

function twitterOAuthHeader(
    string $method,
    string $url,
    string $consumerKey,
    string $consumerSecret,
    string $accessToken,
    string $accessTokenSecret
): string {
    $oauth = [
        'oauth_consumer_key' => $consumerKey,
        'oauth_nonce' => bin2hex(random_bytes(16)),
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => (string)time(),
        'oauth_token' => $accessToken,
        'oauth_version' => '1.0',
    ];

    $baseParams = $oauth;
    ksort($baseParams);
    $encodedParams = [];
    foreach ($baseParams as $key => $value) {
        $encodedParams[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
    }

    $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $encodedParams));
    $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($accessTokenSecret);
    $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

    $header = [];
    foreach ($oauth as $key => $value) {
        $header[] = rawurlencode($key) . '="' . rawurlencode((string)$value) . '"';
    }

    return 'OAuth ' . implode(', ', $header);
}

function getPost(PDO $pdo): void
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿IDが不正です。'], 400);
    }

    $post = findActivePostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    jsonResponse(buildPost($post));
}

function updatePost(PDO $pdo): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = normalizeString($_POST['name'] ?? '');
    $url = normalizeUrl($_POST['url'] ?? null);
    $title = normalizeString($_POST['title'] ?? '');
    $message = normalizeString($_POST['message'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($id === false || $id === null || $name === '' || $title === '' || $message === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => '投稿ID・名前・タイトル・本文・パスワードは必須です。'], 400);
    }

    $post = findActivePostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    if (!password_verify($password, $post['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'パスワードが一致しません。'], 403);
    }

    $settings = loadSettings($pdo);
    $gdgdEnabled = toBoolFlag(($settings['config'] ?? [])['gdgdEnabled'] ?? true);
    $isReply = (int)($post['parent_id'] ?? 0) !== 0;
    $gdgd = $isReply || !$gdgdEnabled ? false : toBoolFlag($_POST['gdgd'] ?? $post['gdgd'] ?? false);
    $tweetOff = (bool)($post['tweet_off'] ?? true);
    $tweetUrl = $post['tweet_url'] ?? null;

    $imagePath = $post['image_path'];
    if (!$isReply && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $newImage = saveUploadedImage($_FILES['file'], $id);
        if ($newImage === null) {
            jsonResponse(['success' => false, 'message' => '画像のアップロードに失敗しました。'], 400);
        }
        $imagePath = $newImage;
    }

    $tweetText = $post['tweet_text'] ?? null;

    $stmt = $pdo->prepare(
        'UPDATE posts SET
            name = :name,
            url = :url,
            title = :title,
            message = :message,
            image_path = :image_path,
            gdgd = :gdgd,
            tweet_off = :tweet_off,
            tweet_text = :tweet_text,
            tweet_url = :tweet_url,
            tweet_like_count = :tweet_like_count,
            tweet_retweet_count = :tweet_retweet_count,
            tweet_comment_count = :tweet_comment_count,
            tweet_impression_count = :tweet_impression_count
         WHERE id = :id'
    );
    $stmt->execute([
        ':id' => $id,
        ':name' => $name,
        ':url' => $url,
        ':title' => $title,
        ':message' => $message,
        ':image_path' => $imagePath,
        ':gdgd' => $gdgd ? 1 : 0,
        ':tweet_off' => $tweetOff ? 1 : 0,
        ':tweet_text' => $tweetText,
        ':tweet_url' => $tweetUrl,
        ':tweet_like_count' => 0,
        ':tweet_retweet_count' => 0,
        ':tweet_comment_count' => 0,
        ':tweet_impression_count' => 0,
    ]);

    jsonResponse(['success' => true, 'message' => '投稿を更新しました。']);
}

function deletePost(PDO $pdo): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? '';

    if ($id === false || $id === null || $password === '') {
        jsonResponse(['success' => false, 'message' => '投稿IDとパスワードは必須です。'], 400);
    }

    $post = findActivePostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    if (!password_verify($password, $post['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'パスワードが一致しません。'], 403);
    }

    $deletedAt = currentTimestamp();

    if ((int)$post['thread_id'] === $id) {
        $deleteStmt = $pdo->prepare('UPDATE posts SET deleted_at = :deleted_at WHERE thread_id = :thread_id AND deleted_at IS NULL');
        $deleteStmt->execute([
            ':thread_id' => $id,
            ':deleted_at' => $deletedAt,
        ]);
    } else {
        $deleteStmt = $pdo->prepare('UPDATE posts SET deleted_at = :deleted_at WHERE id = :id AND deleted_at IS NULL');
        $deleteStmt->execute([
            ':id' => $id,
            ':deleted_at' => $deletedAt,
        ]);
    }

    jsonResponse(['success' => true, 'message' => '投稿を削除しました。']);
}

function listDeletedPosts(PDO $pdo): void
{
    requireAdmin();
    [$limit, $offset] = paginationParams();
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse(array_map(
        fn (array $row): array => buildDeletedPost($pdo, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    ));
}

function restorePost(PDO $pdo): void
{
    requireAdmin();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿IDが不正です。'], 400);
    }

    $post = findPostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    if ((int)$post['thread_id'] === $id) {
        $stmt = $pdo->prepare('UPDATE posts SET deleted_at = NULL WHERE thread_id = :thread_id');
        $stmt->execute([':thread_id' => $id]);
    } else {
        $stmt = $pdo->prepare('UPDATE posts SET deleted_at = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    jsonResponse(['success' => true, 'message' => '投稿を復元しました。']);
}

function requireAdmin(): void
{
    $pdo = getConnection();
    $settings = loadSettings($pdo);
    $hash = $settings['security']['adminPasswordHash'] ?? null;
    $provided = $_POST['admin_password'] ?? $_GET['admin_password'] ?? '';

    if (is_string($hash) && $hash !== '') {
        if (!password_verify((string)$provided, $hash)) {
            jsonResponse(['success' => false, 'message' => '管理者認証に失敗しました。'], 403);
        }
        return;
    }

    $configured = getenv('DOTEITA_ADMIN_PASSWORD') ?: '';
    if ($configured === '' || !hash_equals($configured, (string)$provided)) {
        jsonResponse(['success' => false, 'message' => '管理者認証に失敗しました。'], 403);
    }
}

function adminDeletePosts(PDO $pdo): void
{
    requireAdmin();
    $ids = $_POST['ids'] ?? [];
    if (is_string($ids)) {
        $ids = array_filter(array_map('trim', explode(',', $ids)));
    }
    if (!is_array($ids) || count($ids) === 0) {
        jsonResponse(['success' => false, 'message' => '削除対象が指定されていません。'], 400);
    }

    $deletedAt = currentTimestamp();
    $deleted = 0;
    foreach ($ids as $rawId) {
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id === false) {
            continue;
        }
        $post = findActivePostById($pdo, $id);
        if (!$post) {
            continue;
        }
        if ((int)$post['thread_id'] === $id) {
            $stmt = $pdo->prepare('UPDATE posts SET deleted_at = :deleted_at WHERE thread_id = :thread_id AND deleted_at IS NULL');
            $stmt->execute([':thread_id' => $id, ':deleted_at' => $deletedAt]);
            $deleted += $stmt->rowCount();
        } else {
            $stmt = $pdo->prepare('UPDATE posts SET deleted_at = :deleted_at WHERE id = :id AND deleted_at IS NULL');
            $stmt->execute([':id' => $id, ':deleted_at' => $deletedAt]);
            $deleted += $stmt->rowCount();
        }
    }

    jsonResponse(['success' => true, 'message' => $deleted . '件を削除しました。']);
}

function adminCheckIntegrity(PDO $pdo): void
{
    requireAdmin();
    $orphanReplies = (int)$pdo->query('SELECT COUNT(*) FROM posts r LEFT JOIN posts t ON r.thread_id = t.id WHERE r.parent_id != 0 AND t.id IS NULL')->fetchColumn();
    $missingImages = [];
    $stmt = $pdo->query("SELECT id, image_path FROM posts WHERE image_path IS NOT NULL AND image_path != ''");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!file_exists((string)$row['image_path'])) {
            $missingImages[] = (int)$row['id'];
        }
    }

    jsonResponse([
        'success' => true,
        'message' => 'DBを確認しました。現行版に旧システムインデックス修復は不要です。',
        'orphan_replies' => $orphanReplies,
        'missing_image_post_ids' => $missingImages,
    ]);
}

function exportBackup(PDO $pdo): void
{
    requireAdmin();
    $posts = $pdo->query('SELECT * FROM posts ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    $images = [];
    if (is_dir(STORAGE_DIR)) {
        foreach (glob(STORAGE_DIR . '/*') ?: [] as $path) {
            if (is_file($path)) {
                $images[basename($path)] = base64_encode((string)file_get_contents($path));
            }
        }
    }

    $payload = [
        'backup_version' => 1,
        'exported_at' => currentTimestamp(),
        'posts' => $posts,
        'images' => $images,
        'settings' => loadSettings($pdo),
    ];

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="threadforge-backup-' . date('Ymd-His') . '.json"');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function importBackup(PDO $pdo): void
{
    requireAdmin();
    if (empty($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'バックアップファイルを選択してください。'], 400);
    }

    $payload = json_decode((string)file_get_contents($_FILES['backup']['tmp_name']), true);
    if (!is_array($payload) || ($payload['backup_version'] ?? null) !== 1 || !isset($payload['posts']) || !is_array($payload['posts'])) {
        jsonResponse(['success' => false, 'message' => 'バックアップ形式が正しくありません。'], 400);
    }

    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM posts');
    $stmt = $pdo->prepare(
        'INSERT INTO posts (
            id, thread_id, parent_id, name, url, title, message, image_path, password_hash, created_at, deleted_at, gdgd,
            tweet_off, tweet_text, tweet_url, tweet_like_count, tweet_retweet_count, tweet_comment_count, tweet_impression_count
        ) VALUES (
            :id, :thread_id, :parent_id, :name, :url, :title, :message, :image_path, :password_hash, :created_at, :deleted_at, :gdgd,
            :tweet_off, :tweet_text, :tweet_url, :tweet_like_count, :tweet_retweet_count, :tweet_comment_count, :tweet_impression_count
        )'
    );
    foreach ($payload['posts'] as $row) {
        $stmt->execute([
            ':id' => (int)($row['id'] ?? 0),
            ':thread_id' => (int)($row['thread_id'] ?? 0),
            ':parent_id' => (int)($row['parent_id'] ?? 0),
            ':name' => (string)($row['name'] ?? ''),
            ':url' => $row['url'] ?? null,
            ':title' => (string)($row['title'] ?? ''),
            ':message' => (string)($row['message'] ?? ''),
            ':image_path' => $row['image_path'] ?? null,
            ':password_hash' => $row['password_hash'] ?? null,
            ':created_at' => (string)($row['created_at'] ?? currentTimestamp()),
            ':deleted_at' => $row['deleted_at'] ?? null,
            ':gdgd' => (int)($row['gdgd'] ?? 0),
            ':tweet_off' => (int)($row['tweet_off'] ?? 0),
            ':tweet_text' => $row['tweet_text'] ?? null,
            ':tweet_url' => $row['tweet_url'] ?? null,
            ':tweet_like_count' => (int)($row['tweet_like_count'] ?? 0),
            ':tweet_retweet_count' => (int)($row['tweet_retweet_count'] ?? 0),
            ':tweet_comment_count' => (int)($row['tweet_comment_count'] ?? 0),
            ':tweet_impression_count' => (int)($row['tweet_impression_count'] ?? 0),
        ]);
    }
    $pdo->commit();

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }
    foreach (glob(STORAGE_DIR . '/*') ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    foreach (($payload['images'] ?? []) as $filename => $encoded) {
        file_put_contents(STORAGE_DIR . '/' . basename((string)$filename), base64_decode((string)$encoded));
    }
    if (isset($payload['settings']) && is_array($payload['settings'])) {
        saveSettings($pdo, $payload['settings']);
    }

    jsonResponse(['success' => true, 'message' => 'バックアップをインポートしました。']);
}

function importLegacyBbsnote(PDO $pdo): void
{
    requireAdmin();
    $legacyDir = (string)($_POST['legacy_dir'] ?? 'data');

    try {
        jsonResponse(importLegacyBbsnoteDirectory($pdo, $legacyDir));
    } catch (InvalidArgumentException $exception) {
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 400);
    }
}

function getSettings(PDO $pdo): void
{
    requireAdmin();
    jsonResponse(['success' => true, 'settings' => loadSettings($pdo)]);
}

function publicSettings(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];

    jsonResponse([
        'success' => true,
        'settings' => [
            'config' => [
                'bbsTitle' => (string)($config['bbsTitle'] ?? 'ThreadForge'),
                'homePageUrl' => (string)($config['homePageUrl'] ?? '/'),
                'manualTitle' => (string)($config['manualTitle'] ?? 'ThreadForge 取扱説明書'),
                'manualBody' => (string)($config['manualBody'] ?? defaultManualBody()),
                'tweetEnabled' => toBoolFlag($config['tweetEnabled'] ?? true),
                'gdgdEnabled' => toBoolFlag($config['gdgdEnabled'] ?? true),
                'gdgdLabel' => (string)($config['gdgdLabel'] ?? 'gdgd投稿'),
            ],
        ],
    ]);
}

function updateSettings(PDO $pdo): void
{
    requireAdmin();
    $settings = json_decode((string)($_POST['settings'] ?? ''), true);
    if (!is_array($settings)) {
        jsonResponse(['success' => false, 'message' => '設定データが正しくありません。'], 400);
    }
    saveSettings($pdo, $settings);
    jsonResponse(['success' => true, 'message' => '設定を保存しました。']);
}

function changeAdminPassword(PDO $pdo): void
{
    requireAdmin();
    $newPassword = trim((string)($_POST['new_admin_password'] ?? ''));
    if ($newPassword === '') {
        jsonResponse(['success' => false, 'message' => '新しい管理パスワードを入力してください。'], 400);
    }

    $settings = loadSettings($pdo);
    $settings['security']['adminPasswordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    saveSettings($pdo, $settings);
    jsonResponse(['success' => true, 'message' => '管理パスワードを変更しました。']);
}

function defaultSettings(): array
{
    return [
        'config' => [
            'bbsTitle' => 'ThreadForge',
            'homePageUrl' => '/',
            'manualTitle' => 'ThreadForge 取扱説明書',
            'manualBody' => defaultManualBody(),
            'tweetEnabled' => true,
            'tweetBaseUrl' => 'https://twitter.com/MUGEN87112020/status/',
            'tweetConsumerKey' => '',
            'tweetConsumerSecret' => '',
            'tweetAccessToken' => '',
            'tweetAccessTokenSecret' => '',
            'gdgdEnabled' => true,
            'gdgdLabel' => 'gdgd投稿',
            'logView' => 20,
            'maxUploadBytes' => 5100000,
            'maxImageWidth' => 1280,
            'maxImageHeight' => 960,
        ],
        'skin' => [
            'normalFrameColor' => '#a23dff',
            'gdgdFrameColor' => '#6dffc0',
            'tweetOffFrameColor' => '#888888',
            'backgroundColor' => '#000000',
        ],
        'security' => [
            'adminPasswordHash' => '',
        ],
    ];
}

function defaultManualBody(): string
{
    return implode("\n", [
        'ThreadForge は、スレッド形式で作品や記事を投稿できる掲示板です。',
        '',
        '投稿',
        '新規投稿ではタイトル、本文、画像、gdgd投稿、Tweet OFFを指定できます。',
        'Tweet関連の項目は新規投稿だけで使います。返信では表示されません。',
        '',
        '返信',
        '返信では名前、URL / HOME、本文、パスワードを入力できます。',
        '返信に画像投稿はありません。',
        '',
        '削除と編集',
        '削除は画面上から非表示にしますが、内部データは保持します。',
        '投稿と返信は、投稿時のパスワードで編集または削除できます。',
        '',
        '管理',
        '管理画面では一括削除、バックアップ、インポート、設定変更を行えます。',
    ]);
}

function loadSettings(PDO $pdo): array
{
    $settings = defaultSettings();
    $stmt = $pdo->query('SELECT key, value FROM settings');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $decoded = json_decode((string)$row['value'], true);
        if (is_array($decoded)) {
            $settings[(string)$row['key']] = array_merge($settings[(string)$row['key']] ?? [], $decoded);
        }
    }
    return $settings;
}

function saveSettings(PDO $pdo, array $settings): void
{
    $stmt = $pdo->prepare('REPLACE INTO settings (key, value) VALUES (:key, :value)');
    foreach (['config', 'skin', 'security'] as $key) {
        if (isset($settings[$key]) && is_array($settings[$key])) {
            $stmt->execute([
                ':key' => $key,
                ':value' => json_encode($settings[$key], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }
}

function paginationParams(): array
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50;
    $page = max(1, $page);
    $limit = min(100, max(1, $limit));
    return [$limit, ($page - 1) * $limit];
}
