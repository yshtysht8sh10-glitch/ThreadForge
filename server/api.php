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
    case 'refreshSocialReactions':
        refreshSocialReactions($pdo);
        break;
    case 'exportBackup':
        exportBackup($pdo);
        break;
    case 'importBackup':
        importBackup($pdo);
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
    $tweetEnabled = toBoolFlag($config['tweetEnabled'] ?? false);
    $blueskyEnabled = toBoolFlag($config['blueskyEnabled'] ?? false);
    $mastodonEnabled = toBoolFlag($config['mastodonEnabled'] ?? false);
    $misskeyEnabled = toBoolFlag($config['misskeyEnabled'] ?? false);
    $socialEnabled = $tweetEnabled || $blueskyEnabled || $mastodonEnabled || $misskeyEnabled;
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
    $tweetOff = $isReply || !$socialEnabled ? true : toBoolFlag($_POST['tweet_off'] ?? $_POST['TweetOFF'] ?? false);
    $tweetUrl = null;

    if ($name === '' || $title === '' || $message === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => '名前・タイトル・本文・パスワードは必須です。'], 400);
    }

    if (hasRecentDuplicatePost($pdo, $name, $title, $message)) {
        jsonResponse(['success' => false, 'message' => '同じ内容の連続投稿は少し時間を空けてください。'], 429);
    }

    $createdAt = currentTimestamp();
    $sourceUrl = $_POST['source_url'] ?? null;
    $normalizedSourceUrl = is_string($sourceUrl) ? normalizeUrl($sourceUrl) : null;
    $tweetText = $tweetOff ? null : buildTweetText($name, $title, $message, $normalizedSourceUrl);

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
    if (!$tweetOff && $tweetText !== null && $tweetEnabled) {
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

    if (!$tweetOff) {
        foreach (publishFederatedPostsFromSettings($settings, $name, $title, $message, $normalizedSourceUrl, $insertedId) as $platform => $result) {
            if ($result['success']) {
                saveSocialPublishResult($pdo, $insertedId, $platform, $result);
                $responseMessage .= ' ' . socialPlatformLabel($platform) . 'へ投稿しました。';
            } else {
                $responseMessage .= ' ' . socialPlatformLabel($platform) . '投稿は失敗しました: ' . $result['message'];
            }
        }
    }

    jsonResponse(['success' => true, 'message' => $responseMessage, 'tweet_url' => $tweetUrl]);
}

function fillTweetPostId(string $tweetText, int $postId): string
{
    return preg_replace('/000000/', str_pad((string)$postId, 6, '0', STR_PAD_LEFT), $tweetText, 1) ?? $tweetText;
}

function publishTweetFromSettings(array $settings, string $text, ?string $imagePath, ?string $previousTweetId = null): array
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

    $tweet = twitterCreateTweet($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $text, $mediaIds, $previousTweetId);
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
    array $mediaIds,
    ?string $previousTweetId = null
): array {
    $payload = ['text' => $text];
    if (count($mediaIds) > 0) {
        $payload['media'] = ['media_ids' => $mediaIds];
    }
    if ($previousTweetId !== null && $previousTweetId !== '') {
        $payload['edit_options'] = ['previous_post_id' => $previousTweetId];
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
    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ];
    if (strtoupper($method) !== 'GET') {
        $options[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($curl, $options);

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

    $baseUrl = strtok($url, '?') ?: $url;
    $baseParams = $oauth;
    $query = parse_url($url, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        parse_str($query, $queryParams);
        foreach ($queryParams as $key => $value) {
            $baseParams[(string)$key] = $value;
        }
    }
    ksort($baseParams);
    $encodedParams = [];
    foreach ($baseParams as $key => $value) {
        $encodedParams[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
    }

    $baseString = strtoupper($method) . '&' . rawurlencode($baseUrl) . '&' . rawurlencode(implode('&', $encodedParams));
    $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($accessTokenSecret);
    $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

    $header = [];
    foreach ($oauth as $key => $value) {
        $header[] = rawurlencode($key) . '="' . rawurlencode((string)$value) . '"';
    }

    return 'OAuth ' . implode(', ', $header);
}

function publishFederatedPostsFromSettings(array $settings, string $name, string $title, string $message, ?string $sourceUrl, int $postId): array
{
    $config = $settings['config'] ?? [];
    $results = [];

    if (toBoolFlag($config['blueskyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('bluesky', $name, $title, $message, $sourceUrl), $postId);
        $results['bluesky'] = publishBlueskyPost($config, $text);
    }
    if (toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('mastodon', $name, $title, $message, $sourceUrl), $postId);
        $results['mastodon'] = publishMastodonPost($config, $text);
    }
    if (toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('misskey', $name, $title, $message, $sourceUrl), $postId);
        $results['misskey'] = publishMisskeyPost($config, $text);
    }

    return $results;
}

function updateFederatedPostsFromSettings(array $settings, array $post, string $name, string $title, string $message, ?string $sourceUrl, int $postId): array
{
    $config = $settings['config'] ?? [];
    $results = [];

    if (toBoolFlag($config['blueskyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('bluesky', $name, $title, $message, $sourceUrl), $postId);
        $results['bluesky'] = updateBlueskyPost($config, $text, (string)($post['bluesky_uri'] ?? ''));
    }
    if (toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('mastodon', $name, $title, $message, $sourceUrl), $postId);
        $results['mastodon'] = updateMastodonPost($config, $text, (string)($post['mastodon_id'] ?? ''));
    }
    if (toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('misskey', $name, $title, $message, $sourceUrl), $postId);
        $results['misskey'] = publishMisskeyPost($config, $text);
    }

    return $results;
}

function publishBlueskyPost(array $config, string $text): array
{
    $service = rtrim(trim((string)($config['blueskyServiceUrl'] ?? 'https://bsky.social')), '/');
    $handle = trim((string)($config['blueskyHandle'] ?? ''));
    $password = trim((string)($config['blueskyAppPassword'] ?? ''));
    if ($handle === '' || $password === '') {
        return ['success' => false, 'message' => 'BlueskyのハンドルまたはApp Passwordが未設定です。'];
    }

    $session = httpJsonRequest('POST', $service . '/xrpc/com.atproto.server.createSession', [], [
        'identifier' => $handle,
        'password' => $password,
    ]);
    if (!$session['success']) {
        return $session;
    }

    $accessJwt = (string)($session['body']['accessJwt'] ?? '');
    $did = (string)($session['body']['did'] ?? '');
    if ($accessJwt === '' || $did === '') {
        return ['success' => false, 'message' => 'Blueskyのセッション応答にaccessJwtまたはdidがありません。'];
    }

    $post = httpJsonRequest('POST', $service . '/xrpc/com.atproto.repo.createRecord', [
        'Authorization: Bearer ' . $accessJwt,
    ], [
        'repo' => $did,
        'collection' => 'app.bsky.feed.post',
        'record' => [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => gmdate('c'),
        ],
    ]);
    if (!$post['success']) {
        return $post;
    }

    $uri = (string)($post['body']['uri'] ?? '');
    $cid = (string)($post['body']['cid'] ?? '');
    if ($uri === '') {
        return ['success' => false, 'message' => 'Blueskyの投稿応答にURIがありません。'];
    }

    return [
        'success' => true,
        'uri' => $uri,
        'cid' => $cid,
        'url' => blueskyUrlFromUri($uri, $handle),
    ];
}

function updateBlueskyPost(array $config, string $text, string $existingUri): array
{
    if ($existingUri === '') {
        return publishBlueskyPost($config, $text);
    }

    $service = rtrim(trim((string)($config['blueskyServiceUrl'] ?? 'https://bsky.social')), '/');
    $handle = trim((string)($config['blueskyHandle'] ?? ''));
    $password = trim((string)($config['blueskyAppPassword'] ?? ''));
    if ($handle === '' || $password === '') {
        return ['success' => false, 'message' => 'BlueskyのハンドルまたはApp Passwordが未設定です。'];
    }

    $parts = explode('/', $existingUri);
    $rkey = end($parts);
    if (!is_string($rkey) || $rkey === '') {
        return ['success' => false, 'message' => 'Blueskyの既存URIからrkeyを取得できません。'];
    }

    $session = httpJsonRequest('POST', $service . '/xrpc/com.atproto.server.createSession', [], [
        'identifier' => $handle,
        'password' => $password,
    ]);
    if (!$session['success']) {
        return $session;
    }

    $accessJwt = (string)($session['body']['accessJwt'] ?? '');
    $did = (string)($session['body']['did'] ?? '');
    if ($accessJwt === '' || $did === '') {
        return ['success' => false, 'message' => 'Blueskyのセッション応答にaccessJwtまたはdidがありません。'];
    }

    $post = httpJsonRequest('POST', $service . '/xrpc/com.atproto.repo.putRecord', [
        'Authorization: Bearer ' . $accessJwt,
    ], [
        'repo' => $did,
        'collection' => 'app.bsky.feed.post',
        'rkey' => $rkey,
        'record' => [
            '$type' => 'app.bsky.feed.post',
            'text' => $text,
            'createdAt' => gmdate('c'),
        ],
    ]);
    if (!$post['success']) {
        return $post;
    }

    $uri = (string)($post['body']['uri'] ?? $existingUri);
    $cid = (string)($post['body']['cid'] ?? '');
    return ['success' => true, 'uri' => $uri, 'cid' => $cid, 'url' => blueskyUrlFromUri($uri, $handle)];
}

function publishMastodonPost(array $config, string $text): array
{
    $instance = rtrim(trim((string)($config['mastodonInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['mastodonAccessToken'] ?? ''));
    if ($instance === '' || $token === '') {
        return ['success' => false, 'message' => 'MastodonのインスタンスURLまたはAccess Tokenが未設定です。'];
    }

    $response = httpJsonRequest('POST', $instance . '/api/v1/statuses', [
        'Authorization: Bearer ' . $token,
    ], [
        'status' => $text,
        'visibility' => (string)($config['mastodonVisibility'] ?? 'public'),
    ], false);
    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? '');
    $url = (string)($response['body']['url'] ?? '');
    if ($id === '') {
        return ['success' => false, 'message' => 'Mastodonの投稿応答にIDがありません。'];
    }

    return ['success' => true, 'id' => $id, 'url' => $url];
}

function updateMastodonPost(array $config, string $text, string $existingId): array
{
    if ($existingId === '') {
        return publishMastodonPost($config, $text);
    }

    $instance = rtrim(trim((string)($config['mastodonInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['mastodonAccessToken'] ?? ''));
    if ($instance === '' || $token === '') {
        return ['success' => false, 'message' => 'MastodonのインスタンスURLまたはAccess Tokenが未設定です。'];
    }

    $response = httpJsonRequest('PUT', $instance . '/api/v1/statuses/' . rawurlencode($existingId), [
        'Authorization: Bearer ' . $token,
    ], [
        'status' => $text,
    ], false);
    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? $existingId);
    $url = (string)($response['body']['url'] ?? '');
    return ['success' => true, 'id' => $id, 'url' => $url];
}

function publishMisskeyPost(array $config, string $text): array
{
    $instance = rtrim(trim((string)($config['misskeyInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['misskeyAccessToken'] ?? ''));
    if ($instance === '' || $token === '') {
        return ['success' => false, 'message' => 'MisskeyのインスタンスURLまたはAccess Tokenが未設定です。'];
    }

    $response = httpJsonRequest('POST', $instance . '/api/notes/create', [], [
        'i' => $token,
        'text' => $text,
    ]);
    if (!$response['success']) {
        return $response;
    }

    $note = $response['body']['createdNote'] ?? $response['body'];
    $id = (string)($note['id'] ?? '');
    $url = (string)($note['url'] ?? '');
    if ($url === '' && $id !== '') {
        $url = $instance . '/notes/' . $id;
    }
    if ($id === '') {
        return ['success' => false, 'message' => 'Misskeyの投稿応答にNote IDがありません。'];
    }

    return ['success' => true, 'id' => $id, 'url' => $url];
}

function saveSocialPublishResult(PDO $pdo, int $postId, string $platform, array $result): void
{
    $updates = [
        'bluesky' => ['bluesky_uri' => $result['uri'] ?? null, 'bluesky_cid' => $result['cid'] ?? null, 'bluesky_url' => $result['url'] ?? null],
        'mastodon' => ['mastodon_id' => $result['id'] ?? null, 'mastodon_url' => $result['url'] ?? null],
        'misskey' => ['misskey_id' => $result['id'] ?? null, 'misskey_url' => $result['url'] ?? null],
    ][$platform] ?? [];

    if ($updates === []) {
        return;
    }

    $sets = [];
    $params = [':id' => $postId];
    foreach ($updates as $column => $value) {
        $sets[] = $column . ' = :' . $column;
        $params[':' . $column] = $value;
    }
    $stmt = $pdo->prepare('UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($params);
}

function socialPlatformLabel(string $platform): string
{
    return ['bluesky' => 'Bluesky', 'mastodon' => 'Mastodon', 'misskey' => 'Misskey'][$platform] ?? $platform;
}

function blueskyUrlFromUri(string $uri, string $handle): string
{
    $parts = explode('/', $uri);
    $rkey = end($parts);
    return 'https://bsky.app/profile/' . rawurlencode($handle) . '/post/' . rawurlencode((string)$rkey);
}

function httpJsonRequest(string $method, string $url, array $headers = [], array $payload = [], bool $jsonBody = true): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL拡張が有効ではありません。'];
    }

    $curlHeaders = $headers;
    $body = null;
    if ($method !== 'GET') {
        if ($jsonBody) {
            $curlHeaders[] = 'Content-Type: application/json';
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        } else {
            $body = http_build_query($payload);
        }
    }

    if ($method === 'GET' && $payload !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . queryString($payload);
    }

    $curl = curl_init($url);
    $options = [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $curlHeaders,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ];
    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = $body;
    }
    curl_setopt_array($curl, $options);

    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($raw === false) {
        return ['success' => false, 'message' => 'API通信に失敗しました: ' . $error];
    }

    $decoded = json_decode((string)$raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string)$raw;
        return ['success' => false, 'message' => 'APIがエラーを返しました。HTTP ' . $status . ' ' . $detail];
    }
    if (!is_array($decoded)) {
        return ['success' => false, 'message' => 'API応答がJSONではありません。'];
    }

    return ['success' => true, 'body' => $decoded];
}

function queryString(array $params): string
{
    $pairs = [];
    foreach ($params as $key => $value) {
        foreach ((array)$value as $item) {
            $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$item);
        }
    }
    return implode('&', $pairs);
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
    $config = $settings['config'] ?? [];
    $gdgdEnabled = toBoolFlag($config['gdgdEnabled'] ?? true);
    $tweetEnabled = toBoolFlag($config['tweetEnabled'] ?? false);
    $blueskyEnabled = toBoolFlag($config['blueskyEnabled'] ?? false);
    $mastodonEnabled = toBoolFlag($config['mastodonEnabled'] ?? false);
    $misskeyEnabled = toBoolFlag($config['misskeyEnabled'] ?? false);
    $socialEnabled = $tweetEnabled || $blueskyEnabled || $mastodonEnabled || $misskeyEnabled;
    $isReply = (int)($post['parent_id'] ?? 0) !== 0;
    $gdgd = $isReply || !$gdgdEnabled ? false : toBoolFlag($_POST['gdgd'] ?? $post['gdgd'] ?? false);
    $tweetOff = $isReply || !$socialEnabled ? true : toBoolFlag($_POST['tweet_off'] ?? $post['tweet_off'] ?? false);
    $tweetUrl = $post['tweet_url'] ?? null;

    $imagePath = $post['image_path'];
    if (!$isReply && !empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $newImage = saveUploadedImage($_FILES['file'], $id);
        if ($newImage === null) {
            jsonResponse(['success' => false, 'message' => '画像のアップロードに失敗しました。'], 400);
        }
        $imagePath = $newImage;
    }

    $tweetText = null;
    if (!$tweetOff) {
        $tweetText = fillTweetPostId(buildTweetText($name, $title, $message, null), $id);
    }

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

    $responseMessage = '投稿を更新しました。';
    if (!$tweetOff && !$isReply && $tweetText !== null) {
        if ($tweetEnabled) {
            $tweetResult = publishTweetFromSettings($settings, $tweetText, $imagePath, is_string($tweetUrl) ? socialIdFromUrl($tweetUrl) : null);
            if ($tweetResult['success']) {
                $tweetUrl = $tweetResult['url'];
                $updateTweetUrl = $pdo->prepare('UPDATE posts SET tweet_url = :tweet_url WHERE id = :id');
                $updateTweetUrl->execute([':id' => $id, ':tweet_url' => $tweetUrl]);
                $responseMessage .= ' Xへ反映しました。';
            } else {
                $responseMessage .= ' X反映は失敗しました: ' . $tweetResult['message'];
            }
        }

        foreach (updateFederatedPostsFromSettings($settings, $post, $name, $title, $message, null, $id) as $platform => $result) {
            if ($result['success']) {
                saveSocialPublishResult($pdo, $id, $platform, $result);
                $responseMessage .= ' ' . socialPlatformLabel($platform) . 'へ反映しました。';
            } else {
                $responseMessage .= ' ' . socialPlatformLabel($platform) . '反映は失敗しました: ' . $result['message'];
            }
        }
    }

    jsonResponse(['success' => true, 'message' => $responseMessage]);
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

function refreshSocialReactions(PDO $pdo): void
{
    requireAdmin();
    $settings = loadSettings($pdo);
    $rows = $pdo->query(
        "SELECT * FROM posts
         WHERE deleted_at IS NULL
           AND parent_id = 0
           AND (
             tweet_url IS NOT NULL OR bluesky_uri IS NOT NULL OR mastodon_id IS NOT NULL OR misskey_id IS NOT NULL
           )
         ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    $errors = [];
    foreach ($rows as $row) {
        foreach (fetchSocialReactionsForPost($settings, $row) as $platform => $result) {
            if ($result['success']) {
                saveSocialReactionResult($pdo, (int)$row['id'], $platform, $result);
                $updated++;
            } else {
                $errors[] = '#' . $row['id'] . ' ' . socialPlatformLabel($platform) . ': ' . $result['message'];
            }
        }
    }

    jsonResponse([
        'success' => true,
        'message' => 'SNSリアクションを更新しました。',
        'updated' => $updated,
        'errors' => $errors,
    ]);
}

function fetchSocialReactionsForPost(array $settings, array $row): array
{
    $config = $settings['config'] ?? [];
    $results = [];

    if (!empty($row['tweet_url']) && toBoolFlag($config['tweetEnabled'] ?? false)) {
        $results['x'] = fetchXReactions($config, (string)$row['tweet_url']);
    }
    if (!empty($row['bluesky_uri']) && toBoolFlag($config['blueskyEnabled'] ?? false)) {
        $results['bluesky'] = fetchBlueskyReactions($config, (string)$row['bluesky_uri']);
    }
    if (!empty($row['mastodon_id']) && toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $results['mastodon'] = fetchMastodonReactions($config, (string)$row['mastodon_id']);
    }
    if (!empty($row['misskey_id']) && toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $results['misskey'] = fetchMisskeyReactions($config, (string)$row['misskey_id']);
    }

    return $results;
}

function fetchXReactions(array $config, string $tweetUrl): array
{
    $tweetId = socialIdFromUrl($tweetUrl);
    if ($tweetId === '') {
        return ['success' => false, 'message' => 'X投稿IDをURLから取得できません。'];
    }
    $consumerKey = trim((string)($config['tweetConsumerKey'] ?? ''));
    $consumerSecret = trim((string)($config['tweetConsumerSecret'] ?? ''));
    $accessToken = trim((string)($config['tweetAccessToken'] ?? ''));
    $accessTokenSecret = trim((string)($config['tweetAccessTokenSecret'] ?? ''));
    if ($consumerKey === '' || $consumerSecret === '' || $accessToken === '' || $accessTokenSecret === '') {
        return ['success' => false, 'message' => 'X API設定が未入力です。'];
    }

    $url = 'https://api.twitter.com/2/tweets/' . rawurlencode($tweetId) . '?tweet.fields=public_metrics';
    $response = twitterRequest('GET', $url, $consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, [], false);
    if (!$response['success']) {
        return $response;
    }
    $metrics = $response['body']['data']['public_metrics'] ?? [];
    return [
        'success' => true,
        'likes' => (int)($metrics['like_count'] ?? 0),
        'reposts' => (int)($metrics['retweet_count'] ?? 0),
        'impressions' => (int)($metrics['impression_count'] ?? 0),
    ];
}

function fetchBlueskyReactions(array $config, string $uri): array
{
    $service = rtrim(trim((string)($config['blueskyPublicApiUrl'] ?? 'https://public.api.bsky.app')), '/');
    $response = httpJsonRequest('GET', $service . '/xrpc/app.bsky.feed.getPosts', [], ['uris' => [$uri]]);
    if (!$response['success']) {
        return $response;
    }
    $post = $response['body']['posts'][0] ?? [];
    return [
        'success' => true,
        'likes' => (int)($post['likeCount'] ?? 0),
        'reposts' => (int)($post['repostCount'] ?? 0),
        'quotes' => (int)($post['quoteCount'] ?? 0),
    ];
}

function fetchMastodonReactions(array $config, string $id): array
{
    $instance = rtrim(trim((string)($config['mastodonInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['mastodonAccessToken'] ?? ''));
    if ($instance === '') {
        return ['success' => false, 'message' => 'MastodonインスタンスURLが未設定です。'];
    }
    $headers = $token === '' ? [] : ['Authorization: Bearer ' . $token];
    $response = httpJsonRequest('GET', $instance . '/api/v1/statuses/' . rawurlencode($id), $headers);
    if (!$response['success']) {
        return $response;
    }
    return [
        'success' => true,
        'boosts' => (int)($response['body']['reblogs_count'] ?? 0),
        'favs' => (int)($response['body']['favourites_count'] ?? 0),
    ];
}

function fetchMisskeyReactions(array $config, string $id): array
{
    $instance = rtrim(trim((string)($config['misskeyInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['misskeyAccessToken'] ?? ''));
    if ($instance === '') {
        return ['success' => false, 'message' => 'MisskeyインスタンスURLが未設定です。'];
    }
    $response = httpJsonRequest('POST', $instance . '/api/notes/show', [], ['i' => $token, 'noteId' => $id]);
    if (!$response['success']) {
        return $response;
    }
    return ['success' => true] + misskeyReactionCounts($response['body']['reactions'] ?? []);
}

function misskeyReactionCounts(array $reactions): array
{
    $known = ['🔥' => 'fire', '👀' => 'eyes', '😭' => 'cry', '🤔' => 'thinking', '🎉' => 'party'];
    $counts = ['fire' => 0, 'eyes' => 0, 'cry' => 0, 'thinking' => 0, 'party' => 0, 'other' => 0];
    foreach ($reactions as $reaction => $count) {
        $key = $known[(string)$reaction] ?? null;
        if ($key === null) {
            $counts['other'] += (int)$count;
        } else {
            $counts[$key] += (int)$count;
        }
    }
    return $counts;
}

function saveSocialReactionResult(PDO $pdo, int $postId, string $platform, array $result): void
{
    $maps = [
        'x' => [
            'tweet_like_count' => $result['likes'] ?? 0,
            'tweet_retweet_count' => $result['reposts'] ?? 0,
            'tweet_impression_count' => $result['impressions'] ?? 0,
        ],
        'bluesky' => [
            'bluesky_like_count' => $result['likes'] ?? 0,
            'bluesky_repost_count' => $result['reposts'] ?? 0,
            'bluesky_quote_count' => $result['quotes'] ?? 0,
        ],
        'mastodon' => [
            'mastodon_boost_count' => $result['boosts'] ?? 0,
            'mastodon_fav_count' => $result['favs'] ?? 0,
        ],
        'misskey' => [
            'misskey_fire_count' => $result['fire'] ?? 0,
            'misskey_eyes_count' => $result['eyes'] ?? 0,
            'misskey_cry_count' => $result['cry'] ?? 0,
            'misskey_thinking_count' => $result['thinking'] ?? 0,
            'misskey_party_count' => $result['party'] ?? 0,
            'misskey_other_count' => $result['other'] ?? 0,
        ],
    ];
    $updates = $maps[$platform] ?? [];
    if ($updates === []) {
        return;
    }
    $sets = [];
    $params = [':id' => $postId];
    foreach ($updates as $column => $value) {
        $sets[] = $column . ' = :' . $column;
        $params[':' . $column] = (int)$value;
    }
    $stmt = $pdo->prepare('UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($params);
}

function socialIdFromUrl(string $url): string
{
    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path)) {
        return '';
    }
    $parts = array_values(array_filter(explode('/', $path), static fn($part) => $part !== ''));
    return (string)end($parts);
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
                'tweetEnabled' => toBoolFlag($config['tweetEnabled'] ?? false),
                'blueskyEnabled' => toBoolFlag($config['blueskyEnabled'] ?? false),
                'mastodonEnabled' => toBoolFlag($config['mastodonEnabled'] ?? false),
                'misskeyEnabled' => toBoolFlag($config['misskeyEnabled'] ?? false),
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
            'tweetEnabled' => false,
            'tweetBaseUrl' => 'https://twitter.com/MUGEN87112020/status/',
            'tweetConsumerKey' => '',
            'tweetConsumerSecret' => '',
            'tweetAccessToken' => '',
            'tweetAccessTokenSecret' => '',
            'blueskyEnabled' => false,
            'blueskyServiceUrl' => 'https://bsky.social',
            'blueskyPublicApiUrl' => 'https://public.api.bsky.app',
            'blueskyHandle' => '',
            'blueskyAppPassword' => '',
            'mastodonEnabled' => false,
            'mastodonInstanceUrl' => '',
            'mastodonAccessToken' => '',
            'mastodonVisibility' => 'public',
            'misskeyEnabled' => false,
            'misskeyInstanceUrl' => '',
            'misskeyAccessToken' => '',
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
        '新規投稿ではタイトル、本文、画像、gdgd投稿、SNS転記OFFを指定できます。',
        'SNS転記関連の項目は新規投稿と投稿編集で使います。返信では表示されません。',
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
