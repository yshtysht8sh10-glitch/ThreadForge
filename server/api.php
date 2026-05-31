<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    handleApiRequest();
}

function handleApiRequest(): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    try {
    $action = resolveAction($_GET, $_POST);
    $pdo = getConnection();

    switch ($action) {
        case 'listThreads':
            listThreads($pdo);
            break;
        case 'listAdminThreads':
            listAdminThreads($pdo);
            break;
        case 'listThreadArchiveMeta':
            listThreadArchiveMeta($pdo);
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
        case 'adminStatus':
            adminStatus($pdo);
            break;
        case 'recordAccess':
            recordAccess($pdo);
            break;
        case 'checkLoginId':
            checkLoginId($pdo);
            break;
        case 'registerUser':
            registerUser($pdo);
            break;
        case 'loginUser':
            loginUser($pdo);
            break;
        case 'ssoLogin':
            ssoLogin($pdo);
            break;
        case 'logoutUser':
            logoutUser($pdo);
            break;
        case 'currentUser':
            currentUser($pdo);
            break;
        case 'updateUserProfile':
            updateUserProfile($pdo);
            break;
        case 'listUserDashboard':
            listUserDashboard($pdo);
            break;
        case 'listUserPosts':
            listUserPosts($pdo);
            break;
        case 'claimUserPost':
            claimUserPost($pdo);
            break;
        case 'unclaimUserPost':
            unclaimUserPost($pdo);
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
        case 'listAnalyticsPosts':
            listAnalyticsPosts($pdo);
            break;
        case 'recordPostView':
            recordPostView($pdo);
            break;
        case 'listBoardAnalyticsPosts':
            listBoardAnalyticsPosts($pdo);
            break;
        case 'listRankingPosts':
            listRankingPosts($pdo);
            break;
        case 'restorePost':
            restorePost($pdo);
            break;
        case 'purgePostData':
            purgePostData($pdo);
            break;
        case 'destroyPostNumber':
            destroyPostNumber($pdo);
            break;
        case 'adminDeletePosts':
            adminDeletePosts($pdo);
            break;
        case 'listAdminUsers':
            listAdminUsers($pdo);
            break;
        case 'adminUpdateUser':
            adminUpdateUser($pdo);
            break;
        case 'adminDeleteUser':
            adminDeleteUser($pdo);
            break;
        case 'adminCheckIntegrity':
            adminCheckIntegrity($pdo);
            break;
        case 'renumberPostsByCreatedAt':
            renumberPostsByCreatedAt($pdo);
            break;
        case 'refreshSocialReactions':
            refreshSocialReactions($pdo);
            break;
        case 'cronRefreshSocialReactions':
            cronRefreshSocialReactions($pdo);
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
        case 'initializeAdminPassword':
            initializeAdminPassword($pdo);
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
    } catch (Throwable $e) {
        jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
    }
}

function listThreads(PDO $pdo): void
{
    [$limit, $offset] = paginationParams($pdo);
    $settings = loadSettings($pdo);
    $listOrder = listThreadOrder($settings);
    [$periodWhere, $periodParams] = threadPeriodFilterFromRequest();
    $targetId = filter_input(INPUT_GET, 'target_id', FILTER_VALIDATE_INT);
    if ($targetId !== false && $targetId !== null) {
        $targetStmt = $pdo->prepare('SELECT id, created_at FROM posts WHERE id = :id AND parent_id = 0 AND deleted_at IS NULL LIMIT 1');
        $targetStmt->execute([':id' => $targetId]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if ($target) {
            if ($periodWhere !== '' || $listOrder === 'createdAt') {
                $beforeStmt = $pdo->prepare(
                    'SELECT COUNT(*) FROM posts
                     WHERE parent_id = 0
                       AND deleted_at IS NULL
                       ' . $periodWhere . '
                       AND (created_at > :created_at OR (created_at = :created_at AND id > :id))'
                );
                $beforeStmt->execute(array_merge($periodParams, [
                    ':created_at' => (string)$target['created_at'],
                    ':id' => (int)$target['id'],
                ]));
            } else {
                $beforeStmt = $pdo->prepare(
                    'SELECT COUNT(*) FROM posts
                     WHERE parent_id = 0
                       AND deleted_at IS NULL
                       ' . $periodWhere . '
                       AND id > :id'
                );
                $beforeStmt->execute(array_merge($periodParams, [':id' => (int)$target['id']]));
            }
            $offset = $limit === null ? 0 : intdiv((int)$beforeStmt->fetchColumn(), $limit) * $limit;
        }
    }
    $orderBy = ($periodWhere !== '' || $listOrder === 'createdAt') ? 'p.created_at DESC, p.id DESC' : 'p.id DESC';
    $sql = 'SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.parent_id = 0 AND p.deleted_at IS NULL ' . $periodWhere . ' ORDER BY ' . $orderBy;
    if ($limit !== null) {
        $sql .= ' LIMIT :limit OFFSET :offset';
    }
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    foreach ($periodParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    bindBoardStatTexts($stmt, $pdo);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = array_map(
        fn (array $row): array => buildThreadSummary($pdo, $row, 10),
        $rows
    );
    jsonResponse($result);
}

function listAdminThreads(PDO $pdo): void
{
    requireAdmin();
    [$limit, $offset] = paginationParams($pdo);
    $settings = loadSettings($pdo);
    $listOrder = listThreadOrder($settings);
    [$periodWhere, $periodParams] = threadPeriodFilterFromRequest();
    $orderBy = $listOrder === 'createdAt' ? 'p.created_at DESC, p.id DESC' : 'p.id DESC';
    $sql = 'SELECT ' . selectablePostSelect('p') . ' FROM posts p WHERE p.parent_id = 0 AND p.deleted_at IS NULL ' . $periodWhere . ' ORDER BY ' . $orderBy;
    if ($limit !== null) {
        $sql .= ' LIMIT :limit OFFSET :offset';
    }
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    foreach ($periodParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    jsonResponse(buildAdminThreadSummaries($pdo, $stmt->fetchAll(PDO::FETCH_ASSOC)));
}

function buildAdminThreadSummaries(PDO $pdo, array $threadRows): array
{
    $displayMap = [];
    $displayNumber = 1;
    $displayStmt = $pdo->query('SELECT id FROM posts WHERE parent_id = 0 ORDER BY id ASC');
    foreach ($displayStmt->fetchAll(PDO::FETCH_COLUMN) as $threadId) {
        $displayMap[(int)$threadId] = $displayNumber++;
    }

    $threads = [];
    $threadOrder = [];
    foreach ($threadRows as $row) {
        $post = buildSelectablePost($row);
        $post['display_no'] = $displayMap[(int)$row['id']] ?? (int)$row['id'];
        $post['replies'] = [];
        $post['reply_count'] = 0;
        $threads[(int)$row['id']] = $post;
        $threadOrder[] = (int)$row['id'];
    }

    if ($threads === []) {
        return [];
    }

    if (count($threads) > 500) {
        $countStmt = $pdo->query(
            'SELECT thread_id, COUNT(*) AS reply_count
               FROM posts
              WHERE id != thread_id AND deleted_at IS NULL
              GROUP BY thread_id'
        );
        while ($countRow = $countStmt->fetch(PDO::FETCH_ASSOC)) {
            $threadId = (int)$countRow['thread_id'];
            if (isset($threads[$threadId])) {
                $threads[$threadId]['reply_count'] = (int)$countRow['reply_count'];
            }
        }
        return array_map(static fn(int $threadId): array => $threads[$threadId], $threadOrder);
    }

    $threadPlaceholders = [];
    foreach ($threadOrder as $index => $threadId) {
        $threadPlaceholders[] = ':thread_id_' . $index;
    }
    $replyStmt = $pdo->prepare(
        'SELECT ' . selectablePostSelect('p') . ',
                (SELECT COUNT(*) FROM posts r WHERE r.thread_id = p.thread_id AND r.id != r.thread_id AND r.id <= p.id) AS reply_no
           FROM posts p
           INNER JOIN posts t ON t.id = p.thread_id AND t.parent_id = 0 AND t.deleted_at IS NULL
          WHERE p.id != p.thread_id
            AND p.deleted_at IS NULL
            AND p.thread_id IN (' . implode(', ', $threadPlaceholders) . ')
          ORDER BY p.thread_id ASC, p.created_at ASC, p.id ASC'
    );
    foreach ($threadOrder as $index => $threadId) {
        $replyStmt->bindValue(':thread_id_' . $index, $threadId, PDO::PARAM_INT);
    }
    $replyStmt->execute();
    while ($reply = $replyStmt->fetch(PDO::FETCH_ASSOC)) {
        $threadId = (int)$reply['thread_id'];
        if (!isset($threads[$threadId])) {
            continue;
        }
        $threads[$threadId]['reply_count']++;
        if (count($threads[$threadId]['replies']) >= 10) {
            continue;
        }
        $replyPost = buildSelectablePost($reply);
        $replyPost['display_no'] = $threads[$threadId]['display_no'];
        $replyPost['reply_no'] = (int)$reply['reply_no'];
        $threads[$threadId]['replies'][] = $replyPost;
    }

    return array_map(static fn(int $threadId): array => $threads[$threadId], $threadOrder);
}

function selectablePostSelect(string $alias): string
{
    $prefix = $alias . '.';
    return $prefix . 'id,
        ' . $prefix . 'thread_id,
        ' . $prefix . 'parent_id,
        ' . $prefix . 'name,
        ' . $prefix . 'url,
        ' . $prefix . 'title,
        ' . $prefix . 'message,
        ' . $prefix . 'image_path,
        ' . $prefix . 'created_at,
        ' . $prefix . 'deleted_at,
        ' . $prefix . 'gdgd,
        ' . $prefix . 'tweet_off,
        ' . $prefix . 'user_id,
        (SELECT u.icon_path FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_icon_path,
        (SELECT u.display_name FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_display_name';
}

function buildSelectablePost(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'thread_id' => (int)$row['thread_id'],
        'parent_id' => (int)$row['parent_id'],
        'name' => (string)$row['name'],
        'url' => $row['url'] ?? null,
        'title' => (string)$row['title'],
        'message' => adminSelectableExcerpt((string)$row['message']),
        'image_path' => publicStoragePath($row['image_path'] ?? null),
        'created_at' => (string)$row['created_at'],
        'deleted_at' => $row['deleted_at'] ?? null,
        'gdgd' => (bool)$row['gdgd'],
        'tweet_off' => (bool)$row['tweet_off'],
        'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
        'user_icon_path' => $row['user_icon_path'] ?? null,
        'user_display_name' => $row['user_display_name'] ?? null,
    ];
}

function adminSelectableExcerpt(string $message): string
{
    if (function_exists('mb_substr')) {
        return mb_strlen($message, 'UTF-8') > 240 ? mb_substr($message, 0, 240, 'UTF-8') . '...' : $message;
    }
    return strlen($message) > 720 ? substr($message, 0, 720) . '...' : $message;
}

function listThreadArchiveMeta(PDO $pdo): void
{
    [$periodWhere, $periodParams] = threadPeriodFilterFromRequest();
    $periodStmt = $pdo->query(
        "SELECT substr(created_at, 1, 4) AS year, substr(created_at, 6, 2) AS month, COUNT(*) AS count
         FROM posts
         WHERE parent_id = 0 AND deleted_at IS NULL AND created_at IS NOT NULL AND length(created_at) >= 7
         GROUP BY year, month
         ORDER BY year DESC, month DESC"
    );
    $periods = array_map(static fn(array $row): array => [
        'year' => (string)$row['year'],
        'month' => (string)$row['month'],
        'count' => (int)$row['count'],
    ], $periodStmt->fetchAll(PDO::FETCH_ASSOC));

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM posts p WHERE p.parent_id = 0 AND p.deleted_at IS NULL ' . $periodWhere);
    foreach ($periodParams as $key => $value) {
        $totalStmt->bindValue($key, $value);
    }
    $totalStmt->execute();

    jsonResponse(['success' => true, 'periods' => $periods, 'total' => (int)$totalStmt->fetchColumn()]);
}

function buildThreadSummary(PDO $pdo, array $thread, int $replyLimit): array
{
    $post = buildPost($thread);
    $post['display_no'] = threadDisplayNo($pdo, (int)$thread['id']);

    $replyStmt = $pdo->prepare('SELECT ' . postSelectWithUserIcon('p') . ' FROM posts p WHERE p.thread_id = :thread_id AND p.id != :thread_id AND p.deleted_at IS NULL ORDER BY p.created_at ASC LIMIT :limit');
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

function postSelectWithBoardStats(string $alias): string
{
    $prefix = $alias . '.';
    return $alias . '.*,
        (SELECT u.icon_path FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_icon_path,
        (SELECT u.display_name FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_display_name,
        ' . postRevisionSelect($alias) . ',
        (SELECT COUNT(*) FROM posts r WHERE r.thread_id = ' . $prefix . 'id AND r.parent_id != 0 AND r.deleted_at IS NULL) AS reply_count,
        (SELECT COUNT(*) FROM posts r WHERE r.thread_id = ' . $prefix . 'id AND r.parent_id != 0 AND r.deleted_at IS NULL AND r.message = :eejanaika_text) AS eejanaika_count,
        (SELECT COUNT(*) FROM posts r WHERE r.thread_id = ' . $prefix . 'id AND r.parent_id != 0 AND r.deleted_at IS NULL AND r.message = :omigoto_text) AS omigoto_count,
        (SELECT COUNT(*) FROM posts r WHERE r.thread_id = ' . $prefix . 'id AND r.parent_id != 0 AND r.deleted_at IS NULL AND r.message = :goodjob_text) AS goodjob_count';
}

function postSelectWithUserIcon(string $alias): string
{
    $prefix = $alias . '.';
    return $alias . '.*,
        (SELECT u.icon_path FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_icon_path,
        (SELECT u.display_name FROM users u WHERE u.id = ' . $prefix . 'user_id) AS user_display_name,
        ' . postRevisionSelect($alias);
}

function postRevisionSelect(string $alias): string
{
    $postId = $alias . '.id';
    return '(SELECT COUNT(*) FROM post_revisions pr WHERE pr.post_id = ' . $postId . ') AS revision_count,
        (SELECT group_concat(pr2.revised_at, char(10)) FROM post_revisions pr2 WHERE pr2.post_id = ' . $postId . ') AS revision_dates';
}

function bindBoardStatTexts(PDOStatement $stmt, PDO $pdo): void
{
    $config = loadSettings($pdo)['config'] ?? [];
    $stmt->bindValue(':eejanaika_text', (string)($config['eejanaikaEejanaikaText'] ?? 'ええじゃないか'), PDO::PARAM_STR);
    $stmt->bindValue(':omigoto_text', (string)($config['eejanaikaOmigotoText'] ?? 'お美事にございまする'), PDO::PARAM_STR);
    $stmt->bindValue(':goodjob_text', (string)($config['eejanaikaGoodjobText'] ?? 'いい仕事してますねぇ'), PDO::PARAM_STR);
}

function getThread(PDO $pdo): void
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => 'スレッドIDが不正です。'], 400);
    }

    $stmt = $pdo->prepare('SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.id = :id AND p.deleted_at IS NULL LIMIT 1');
    bindBoardStatTexts($stmt, $pdo);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        jsonResponse(['success' => false, 'message' => 'スレッドが見つかりません。'], 404);
    }

    $replyStmt = $pdo->prepare('SELECT ' . postSelectWithUserIcon('p') . ' FROM posts p WHERE p.thread_id = :thread_id AND p.id != :thread_id AND p.deleted_at IS NULL ORDER BY p.created_at ASC');
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

    [$limit, $offset] = paginationParams($pdo);
    $pattern = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
    $scope = $_GET['scope'] ?? 'all';
    $kinds = $_GET['kinds'] ?? 'all';
    $order = ($_GET['order'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';
    $where = match ($scope) {
        'title' => 'p.title LIKE :q ESCAPE "\\"',
        'message' => 'p.message LIKE :q ESCAPE "\\"',
        'name' => 'p.name LIKE :q ESCAPE "\\"',
        default => '(p.title LIKE :q ESCAPE "\\" OR p.message LIKE :q ESCAPE "\\" OR p.name LIKE :q ESCAPE "\\")',
    };
    $kindWhere = match ($kinds) {
        'posts' => ' AND p.parent_id = 0',
        'replies' => ' AND p.parent_id != 0',
        'none' => ' AND 1 = 0',
        default => '',
    };
    $sql = 'SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.deleted_at IS NULL AND ' . $where . $kindWhere . ' ORDER BY p.created_at ' . $order . ', p.id ' . $order;
    if ($limit !== null) {
        $sql .= ' LIMIT :limit OFFSET :offset';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q', $pattern, PDO::PARAM_STR);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    bindBoardStatTexts($stmt, $pdo);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(array_map(fn (array $row): array => decorateUserPost($pdo, $row), $rows));
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

function recordAccess(PDO $pdo): void
{
    $date = (new DateTimeImmutable('now', new DateTimeZone('Asia/Tokyo')))->format('Y-m-d');
    $stmt = $pdo->prepare(
        'INSERT INTO access_counts (access_date, count) VALUES (:access_date, 1)
         ON CONFLICT(access_date) DO UPDATE SET count = count + 1'
    );
    $stmt->execute([':access_date' => $date]);
    $select = $pdo->prepare('SELECT count FROM access_counts WHERE access_date = :access_date');
    $select->execute([':access_date' => $date]);
    jsonResponse(['success' => true, 'access_count' => (int)$select->fetchColumn()]);
}

function checkLoginId(PDO $pdo): void
{
    $loginId = normalizeLoginId($_GET['login_id'] ?? $_POST['login_id'] ?? '');
    if ($loginId === '') {
        jsonResponse(['success' => true, 'available' => false, 'message' => 'IDは3-40文字の半角英数字、_、.、-で入力してください。']);
    }
    jsonResponse([
        'success' => true,
        'available' => findUserByLoginId($pdo, $loginId) === null,
    ]);
}

function registerUser(PDO $pdo): void
{
    $loginId = normalizeLoginId($_POST['login_id'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $displayName = normalizeString($_POST['display_name'] ?? $loginId);
    $postPassword = normalizeString($_POST['post_password'] ?? '');
    $homeUrl = normalizeUrl($_POST['home_url'] ?? null);

    if ($loginId === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => 'IDとログインパスワードを入力してください。'], 400);
    }
    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'message' => 'ログインパスワードは8文字以上にしてください。'], 400);
    }
    if ($displayName === '' || utf8Length($displayName) > 30) {
        jsonResponse(['success' => false, 'message' => '名前は1-30文字で入力してください。'], 400);
    }
    if (findUserByLoginId($pdo, $loginId) !== null) {
        jsonResponse(['success' => false, 'message' => 'そのIDは既に使われています。'], 409);
    }

    $now = currentTimestamp();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (login_id, password_hash, display_name, post_password, home_url, icon_path, created_at, updated_at)
             VALUES (:login_id, :password_hash, :display_name, :post_password, :home_url, null, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':login_id' => $loginId,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':display_name' => $displayName,
            ':post_password' => $postPassword,
            ':home_url' => $homeUrl,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
    } catch (PDOException $exception) {
        jsonResponse(['success' => false, 'message' => 'そのIDは既に使われています。'], 409);
    }

    $userId = (int)$pdo->lastInsertId();
    if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $iconPath = saveUploadedUserIcon($_FILES['icon'], $userId);
        if ($iconPath !== null) {
            $update = $pdo->prepare('UPDATE users SET icon_path = :icon_path WHERE id = :id');
            $update->execute([':icon_path' => $iconPath, ':id' => $userId]);
        }
    }

    $user = findUserById($pdo, $userId);
    jsonResponse(['success' => true, 'token' => createUserSession($pdo, $userId), 'user' => buildUser($user)]);
}

function loginUser(PDO $pdo): void
{
    $loginId = normalizeLoginId($_POST['login_id'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $user = findUserByLoginId($pdo, $loginId);
    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'IDまたはログインパスワードが違います。'], 403);
    }

    jsonResponse(['success' => true, 'token' => createUserSession($pdo, (int)$user['id']), 'user' => buildUser($user)]);
}

function ssoLogin(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $enabled = toBoolFlag($config['ssoEnabled'] ?? false);
    $secret = trim((string)($config['ssoSharedSecret'] ?? ''));
    if ($secret === '') {
        $secret = trim((string)(getenv('THREADFORGE_SSO_SECRET') ?: ''));
    }
    if (!$enabled || $secret === '') {
        jsonResponse(['success' => false, 'message' => 'SSOログインは有効化されていません。'], 403);
    }

    $token = trim((string)($_POST['token'] ?? $_GET['token'] ?? $_POST['sso'] ?? $_GET['sso'] ?? ''));
    $payload = verifySsoToken($token, $secret);
    $loginId = normalizeLoginId((string)($payload['login_id'] ?? $payload['sub'] ?? ''));
    $displayName = normalizeString((string)($payload['display_name'] ?? $payload['name'] ?? $loginId));
    $postPassword = normalizeString((string)($payload['post_password'] ?? ''));
    $homeUrl = normalizeUrl(isset($payload['home_url']) ? (string)$payload['home_url'] : null);

    if ($loginId === '') {
        jsonResponse(['success' => false, 'message' => 'SSOトークンのIDが正しくありません。'], 400);
    }
    if ($displayName === '') {
        $displayName = $loginId;
    }
    if (utf8Length($displayName) > 30) {
        $displayName = function_exists('mb_substr') ? mb_substr($displayName, 0, 30, 'UTF-8') : substr($displayName, 0, 30);
    }
    if (utf8Length($postPassword) > 8) {
        $postPassword = function_exists('mb_substr') ? mb_substr($postPassword, 0, 8, 'UTF-8') : substr($postPassword, 0, 8);
    }

    $now = currentTimestamp();
    $user = findUserByLoginId($pdo, $loginId);
    if (!$user) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (login_id, password_hash, display_name, post_password, home_url, icon_path, created_at, updated_at)
             VALUES (:login_id, :password_hash, :display_name, :post_password, :home_url, null, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':login_id' => $loginId,
            ':password_hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
            ':display_name' => $displayName,
            ':post_password' => $postPassword,
            ':home_url' => $homeUrl,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);
        $user = findUserById($pdo, (int)$pdo->lastInsertId());
    } else {
        $nextPostPassword = $postPassword !== '' ? $postPassword : (string)($user['post_password'] ?? '');
        $nextHomeUrl = array_key_exists('home_url', $payload) ? $homeUrl : ($user['home_url'] ?? null);
        $update = $pdo->prepare(
            'UPDATE users
             SET display_name = :display_name,
                 post_password = :post_password,
                 home_url = :home_url,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $update->execute([
            ':display_name' => $displayName,
            ':post_password' => $nextPostPassword,
            ':home_url' => $nextHomeUrl,
            ':updated_at' => $now,
            ':id' => (int)$user['id'],
        ]);
        $user = findUserById($pdo, (int)$user['id']);
    }

    jsonResponse(['success' => true, 'token' => createUserSession($pdo, (int)$user['id']), 'user' => buildUser($user)]);
}

function verifySsoToken(string $token, string $secret): array
{
    if ($token === '' || !str_contains($token, '.')) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンが正しくありません。'], 400);
    }
    [$payloadPart, $signaturePart] = explode('.', $token, 2);
    $expected = base64UrlEncode(hash_hmac('sha256', $payloadPart, $secret, true));
    if (!hash_equals($expected, $signaturePart)) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンの署名が正しくありません。'], 403);
    }
    $decoded = base64UrlDecode($payloadPart);
    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンの内容が正しくありません。'], 400);
    }
    $now = time();
    $expiresAt = isset($payload['exp']) ? (int)$payload['exp'] : 0;
    if ($expiresAt <= $now) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンの有効期限が切れています。'], 403);
    }
    if (isset($payload['iat']) && (int)$payload['iat'] > $now + 300) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンの発行日時が正しくありません。'], 403);
    }
    return $payload;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): string
{
    $padded = str_pad(strtr($value, '-_', '+/'), strlen($value) % 4 === 0 ? strlen($value) : strlen($value) + 4 - strlen($value) % 4, '=', STR_PAD_RIGHT);
    $decoded = base64_decode($padded, true);
    if ($decoded === false) {
        jsonResponse(['success' => false, 'message' => 'SSOトークンのBase64が正しくありません。'], 400);
    }
    return $decoded;
}

function logoutUser(PDO $pdo): void
{
    $token = authToken();
    if ($token !== '') {
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE token = :token');
        $stmt->execute([':token' => $token]);
    }
    jsonResponse(['success' => true]);
}

function currentUser(PDO $pdo): void
{
    $user = requireUser($pdo);
    jsonResponse(['success' => true, 'user' => buildUser($user)]);
}

function updateUserProfile(PDO $pdo): void
{
    $user = requireUser($pdo);
    $displayName = normalizeString($_POST['display_name'] ?? $user['display_name']);
    $postPassword = normalizeString($_POST['post_password'] ?? $user['post_password']);
    $homeUrl = normalizeUrl($_POST['home_url'] ?? null);
    if ($displayName === '') {
        jsonResponse(['success' => false, 'message' => '名前を入力してください。'], 400);
    }
    if (utf8Length($displayName) > 30) {
        jsonResponse(['success' => false, 'message' => '名前は1-30文字で入力してください。'], 400);
    }

    $iconPath = $user['icon_path'] ?? null;
    if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $newIcon = saveUploadedUserIcon($_FILES['icon'], (int)$user['id']);
        if ($newIcon === null) {
            jsonResponse(['success' => false, 'message' => 'アイコンの保存に失敗しました。'], 400);
        }
        $iconPath = $newIcon;
    }

    $stmt = $pdo->prepare(
        'UPDATE users SET display_name = :display_name, post_password = :post_password, home_url = :home_url, icon_path = :icon_path, updated_at = :updated_at WHERE id = :id'
    );
    $stmt->execute([
        ':id' => (int)$user['id'],
        ':display_name' => $displayName,
        ':post_password' => $postPassword,
        ':home_url' => $homeUrl,
        ':icon_path' => $iconPath,
        ':updated_at' => currentTimestamp(),
    ]);

    jsonResponse(['success' => true, 'user' => buildUser(findUserById($pdo, (int)$user['id']))]);
}

function listUserDashboard(PDO $pdo): void
{
    $user = requireUser($pdo);
    $userId = (int)$user['id'];
    $stmt = $pdo->prepare(
        'SELECT ' . postSelectWithBoardStats('p') . ',
            CASE WHEN p.user_id = :can_manage_user_id THEN 1 ELSE 0 END AS can_manage,
            CASE WHEN c.post_id IS NOT NULL THEN 1 ELSE 0 END AS claimed_by_user
         FROM posts p
         LEFT JOIN user_post_claims c ON c.post_id = p.id AND c.user_id = :claim_join_user_id
         WHERE p.deleted_at IS NULL
           AND (p.user_id = :owner_user_id OR c.user_id = :claim_filter_user_id)
         ORDER BY p.created_at DESC'
    );
    bindBoardStatTexts($stmt, $pdo);
    $stmt->bindValue(':can_manage_user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':claim_join_user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':claim_filter_user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $posts = array_map(
        fn (array $row): array => decorateUserPost($pdo, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    jsonResponse([
        'success' => true,
        'posts' => $posts,
        'analytics_posts' => array_values(array_filter($posts, fn (array $post): bool => (int)$post['parent_id'] === 0)),
    ]);
}

function listUserPosts(PDO $pdo): void
{
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    if ($userId === false || $userId === null) {
        jsonResponse(['success' => false, 'message' => 'ユーザーIDが不正です。'], 400);
    }
    $user = findUserById($pdo, $userId);
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'ユーザーが見つかりません。'], 404);
    }

    $stmt = $pdo->prepare(
        'SELECT ' . postSelectWithBoardStats('p') . ',
            CASE WHEN c.post_id IS NOT NULL THEN 1 ELSE 0 END AS claimed_by_user
         FROM posts p
         LEFT JOIN user_post_claims c ON c.post_id = p.id AND c.user_id = :claim_join_user_id
         WHERE p.deleted_at IS NULL
           AND p.parent_id = 0
           AND (p.user_id = :owner_user_id OR c.user_id = :claim_filter_user_id)
         ORDER BY p.created_at DESC'
    );
    bindBoardStatTexts($stmt, $pdo);
    $stmt->bindValue(':claim_join_user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':owner_user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':claim_filter_user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $posts = array_map(
        fn (array $row): array => withDisplayNo($pdo, buildPost($row), (int)$row['id']),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    jsonResponse([
        'success' => true,
        'user' => buildUser($user),
        'posts' => $posts,
    ]);
}

function claimUserPost(PDO $pdo): void
{
    $user = requireUser($pdo);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿Noが不正です。'], 400);
    }

    $displayPostId = postIdFromDisplayNo($pdo, $id);
    if ($displayPostId === null) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }
    $id = $displayPostId;
    $post = findPostById($pdo, $id);
    if (!$post || $post['deleted_at'] !== null) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    $existsStmt = $pdo->prepare('SELECT 1 FROM user_post_claims WHERE user_id = :user_id AND post_id = :post_id');
    $existsStmt->execute([':user_id' => (int)$user['id'], ':post_id' => $id]);
    if (!$existsStmt->fetchColumn()) {
        $stmt = $pdo->prepare('INSERT INTO user_post_claims (user_id, post_id, created_at) VALUES (:user_id, :post_id, :created_at)');
        $stmt->execute([
            ':user_id' => (int)$user['id'],
            ':post_id' => $id,
            ':created_at' => currentTimestamp(),
        ]);
    }

    jsonResponse(['success' => true, 'message' => '自分の作品として登録しました。']);
}

function unclaimUserPost(PDO $pdo): void
{
    $user = requireUser($pdo);
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿Noが不正です。'], 400);
    }

    $displayPostId = postIdFromDisplayNo($pdo, $id);
    if ($displayPostId === null) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }
    $id = $displayPostId;

    $stmt = $pdo->prepare('DELETE FROM user_post_claims WHERE user_id = :user_id AND post_id = :post_id');
    $stmt->execute([
        ':user_id' => (int)$user['id'],
        ':post_id' => $id,
    ]);

    jsonResponse(['success' => true, 'message' => '自分の作品から解除しました。']);
}

function decorateUserPost(PDO $pdo, array $row): array
{
    $post = buildPost($row);
    if ((int)$row['parent_id'] === 0) {
        return withDisplayNo($pdo, $post, (int)$row['id']);
    }
    return withReplyNo($pdo, $post, (int)$row['thread_id'], (int)$row['id']);
}

function postIdFromDisplayNo(PDO $pdo, int $displayNo): ?int
{
    if ($displayNo < 1) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id FROM posts WHERE parent_id = 0 ORDER BY id ASC LIMIT 1 OFFSET :offset');
    $stmt->bindValue(':offset', $displayNo - 1, PDO::PARAM_INT);
    $stmt->execute();
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int)$id;
}

function isPresetCommentPost(PDO $pdo, array $post): bool
{
    if ((int)($post['parent_id'] ?? 0) === 0) {
        return false;
    }
    $config = loadSettings($pdo)['config'] ?? [];
    return isPresetCommentMessage($config, (string)($post['message'] ?? ''));
}

function isPresetCommentMessage(array $config, string $message): bool
{
    return in_array($message, [
        (string)($config['eejanaikaEejanaikaText'] ?? 'ええじゃないか'),
        (string)($config['eejanaikaOmigotoText'] ?? 'お美事にございまする'),
        (string)($config['eejanaikaGoodjobText'] ?? 'いい仕事してますねぇ'),
    ], true);
}

function adminPasswordHash(PDO $pdo): string
{
    $settings = loadSettings($pdo);
    return (string)($settings['security']['adminPasswordHash'] ?? '');
}

function adminPasswordMatches(PDO $pdo, string $password): bool
{
    if ($password === '') {
        return false;
    }

    $hash = adminPasswordHash($pdo);
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    $configured = getenv('THREADFORGE_ADMIN_PASSWORD') ?: '';
    return hash_equals($configured, $password);
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
    $gdgdEnabled = toBoolFlag($config['gdgdEnabled'] ?? false);
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
    $socialHashtags = (string)($config['socialHashtags'] ?? '#art');

    $user = optionalUser($pdo);
    $userId = $user ? (int)$user['id'] : null;

    if ($name === '' || $title === '' || $message === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => '名前・タイトル・本文・パスワードは必須です。'], 400);
    }

    if (hasRecentDuplicatePost($pdo, $name, $title, $message)) {
        jsonResponse(['success' => false, 'message' => '同じ内容の連続投稿は少し時間を空けてください。'], 429);
    }

    $createdAt = currentTimestamp();
    $sourceUrl = $_POST['source_url'] ?? defaultBoardPostUrl();
    $normalizedSourceUrl = is_string($sourceUrl) ? normalizeUrl($sourceUrl) : null;
    $tweetText = $tweetOff ? null : buildTweetText($name, $title, $message, $normalizedSourceUrl, $socialHashtags);

    if ($threadId === 0) {
        $parentId = 0;
    }

    $isPresetReply = $isReply && isPresetCommentMessage($config, $message);
    $adminHash = $isPresetReply ? adminPasswordHash($pdo) : '';
    $hash = $adminHash !== '' ? $adminHash : password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO posts (
            thread_id, parent_id, name, url, title, message, image_path, password_hash, created_at, gdgd, user_id,
            tweet_off, tweet_text, tweet_url, tweet_like_count, tweet_retweet_count, tweet_comment_count, tweet_impression_count
        ) VALUES (
            :thread_id, :parent_id, :name, :url, :title, :message, :image_path, :password_hash, :created_at, :gdgd, :user_id,
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
        ':user_id' => $userId,
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
        $imagePath = saveUploadedImage($_FILES['file'], $insertedId, allowedImageExtensionsFromConfig($config));
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
        foreach (publishFederatedPostsFromSettings($settings, $name, $title, $message, $normalizedSourceUrl, $insertedId, $imagePath) as $platform => $result) {
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
    return preg_replace('/000000/', str_pad((string)$postId, 6, '0', STR_PAD_LEFT), $tweetText) ?? $tweetText;
}

function defaultBoardPostUrl(): ?string
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (!is_string($origin) || trim($origin) === '') {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (is_string($referer) && trim($referer) !== '') {
            $parts = parse_url($referer);
            if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                $origin = $parts['scheme'] . '://' . $parts['host'] . $port;
            }
        }
    }

    $origin = trim((string)$origin);
    if ($origin === '' || !preg_match('/^https?:\/\//i', $origin)) {
        return null;
    }

    return rtrim($origin, '/') . '/#post-000000';
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

function publishFederatedPostsFromSettings(array $settings, string $name, string $title, string $message, ?string $sourceUrl, int $postId, ?string $imagePath = null): array
{
    $config = $settings['config'] ?? [];
    $results = [];

    if (toBoolFlag($config['blueskyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('bluesky', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['bluesky'] = publishBlueskyPost($config, $text, $imagePath);
    }
    if (toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('mastodon', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['mastodon'] = publishMastodonPost($config, $text, $imagePath);
    }
    if (toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('misskey', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['misskey'] = publishMisskeyPost($config, $text, $imagePath);
    }

    return $results;
}

function updateFederatedPostsFromSettings(array $settings, array $post, string $name, string $title, string $message, ?string $sourceUrl, int $postId, ?string $imagePath = null): array
{
    $config = $settings['config'] ?? [];
    $results = [];

    if (toBoolFlag($config['blueskyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('bluesky', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['bluesky'] = updateBlueskyPost($config, $text, (string)($post['bluesky_uri'] ?? ''), $imagePath);
    }
    if (toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('mastodon', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['mastodon'] = updateMastodonPost($config, $text, (string)($post['mastodon_id'] ?? ''), $imagePath);
    }
    if (toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('misskey', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['misskey'] = publishMisskeyPost($config, $text, $imagePath);
    }

    return $results;
}

function publishBlueskyPost(array $config, string $text, ?string $imagePath = null): array
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

    $record = [
        '$type' => 'app.bsky.feed.post',
        'text' => $text,
        'createdAt' => gmdate('c'),
    ];
    $embed = blueskyImageEmbed($service, $accessJwt, $imagePath);
    if (!$embed['success']) {
        return $embed;
    }
    if (isset($embed['embed'])) {
        $record['embed'] = $embed['embed'];
    }

    $post = httpJsonRequest('POST', $service . '/xrpc/com.atproto.repo.createRecord', [
        'Authorization: Bearer ' . $accessJwt,
    ], [
        'repo' => $did,
        'collection' => 'app.bsky.feed.post',
        'record' => $record,
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

function updateBlueskyPost(array $config, string $text, string $existingUri, ?string $imagePath = null): array
{
    if ($existingUri === '') {
        return publishBlueskyPost($config, $text, $imagePath);
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

    $record = [
        '$type' => 'app.bsky.feed.post',
        'text' => $text,
        'createdAt' => gmdate('c'),
    ];
    $embed = blueskyImageEmbed($service, $accessJwt, $imagePath);
    if (!$embed['success']) {
        return $embed;
    }
    if (isset($embed['embed'])) {
        $record['embed'] = $embed['embed'];
    }

    $post = httpJsonRequest('POST', $service . '/xrpc/com.atproto.repo.putRecord', [
        'Authorization: Bearer ' . $accessJwt,
    ], [
        'repo' => $did,
        'collection' => 'app.bsky.feed.post',
        'rkey' => $rkey,
        'record' => $record,
    ]);
    if (!$post['success']) {
        return $post;
    }

    $uri = (string)($post['body']['uri'] ?? $existingUri);
    $cid = (string)($post['body']['cid'] ?? '');
    return ['success' => true, 'uri' => $uri, 'cid' => $cid, 'url' => blueskyUrlFromUri($uri, $handle)];
}

function publishMastodonPost(array $config, string $text, ?string $imagePath = null): array
{
    $instance = rtrim(trim((string)($config['mastodonInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['mastodonAccessToken'] ?? ''));

    if ($instance === '' || $token === '') {
        socialDebugLog('mastodon config missing');
        return ['success' => false, 'message' => 'Mastodon instance URL or Access Token is not configured.'];
    }

    $payload = [
        'status' => $text,
        'visibility' => (string)($config['mastodonVisibility'] ?? 'public'),
    ];

    socialDebugLog('mastodon post start', [
        'instance' => $instance,
        'visibility' => $payload['visibility'],
        'status_length' => mb_strlen($text, 'UTF-8'),
        'has_image' => $imagePath !== null,
        'image_path' => $imagePath,
        'image_exists' => $imagePath !== null ? is_file($imagePath) : null,
    ]);

    $mediaId = mastodonUploadMedia($instance, $token, $imagePath);

    socialDebugLog('mastodon media upload result', [
        'result' => $mediaId,
    ]);

    if (!$mediaId['success']) {
        return $mediaId;
    }

    if (isset($mediaId['id'])) {
        $payload['media_ids'] = [$mediaId['id']];
    }

    socialDebugLog('mastodon status request', [
        'url' => $instance . '/api/v1/statuses',
        'payload' => [
            'status_length' => mb_strlen($payload['status'], 'UTF-8'),
            'visibility' => $payload['visibility'],
            'media_ids' => $payload['media_ids'] ?? [],
        ],
    ]);

    $response = httpJsonRequest(
        'POST',
        $instance . '/api/v1/statuses',
        [
            'Authorization: Bearer ' . $token,
        ],
        $payload,
        false
    );

    socialDebugLog('mastodon status post response', [
        'response' => $response,
    ]);

    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? '');
    $url = (string)($response['body']['url'] ?? '');

    if ($id === '') {
        socialDebugLog('mastodon response has no id', [
            'response' => $response,
        ]);
        return ['success' => false, 'message' => 'Mastodon status response has no ID.'];
    }

    socialDebugLog('mastodon post success', [
        'id' => $id,
        'url' => $url,
    ]);

    return ['success' => true, 'id' => $id, 'url' => $url];
}

function updateMastodonPost(array $config, string $text, string $existingId, ?string $imagePath = null): array
{
    if ($existingId === '') {
        return publishMastodonPost($config, $text, $imagePath);
    }

    $instance = rtrim(trim((string)($config['mastodonInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['mastodonAccessToken'] ?? ''));
    if ($instance === '' || $token === '') {
        return ['success' => false, 'message' => 'MastodonのインスタンスURLまたはAccess Tokenが未設定です。'];
    }

    $payload = ['status' => $text];
    $mediaId = mastodonUploadMedia($instance, $token, $imagePath);
    if (!$mediaId['success']) {
        return $mediaId;
    }
    if (isset($mediaId['id'])) {
        $payload['media_ids'] = [$mediaId['id']];
    }

    $response = httpJsonRequest('PUT', $instance . '/api/v1/statuses/' . rawurlencode($existingId), [
        'Authorization: Bearer ' . $token,
    ], $payload, false);
    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? $existingId);
    $url = (string)($response['body']['url'] ?? '');
    return ['success' => true, 'id' => $id, 'url' => $url];
}

function publishMisskeyPost(array $config, string $text, ?string $imagePath = null): array
{
    $instance = rtrim(trim((string)($config['misskeyInstanceUrl'] ?? '')), '/');
    $token = trim((string)($config['misskeyAccessToken'] ?? ''));
    if ($instance === '' || $token === '') {
        return ['success' => false, 'message' => 'MisskeyのインスタンスURLまたはAccess Tokenが未設定です。'];
    }

    $payload = [
        'i' => $token,
        'text' => $text,
    ];
    $fileId = misskeyUploadFile($instance, $token, $imagePath);
    if (!$fileId['success']) {
        return $fileId;
    }
    if (isset($fileId['id'])) {
        $payload['fileIds'] = [$fileId['id']];
    }

    $response = httpJsonRequest('POST', $instance . '/api/notes/create', [], $payload);
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

function blueskyImageEmbed(string $service, string $accessJwt, ?string $imagePath): array
{
    if ($imagePath === null || !is_file($imagePath)) {
        return ['success' => true];
    }

    $mimeType = imageMimeType($imagePath);
    if ($mimeType === null) {
        return ['success' => false, 'message' => 'Bluesky image type is not supported.'];
    }

    $upload = httpRawRequest('POST', $service . '/xrpc/com.atproto.repo.uploadBlob', [
        'Authorization: Bearer ' . $accessJwt,
        'Content-Type: ' . $mimeType,
    ], (string)file_get_contents($imagePath));
    if (!$upload['success']) {
        return $upload;
    }

    $blob = $upload['body']['blob'] ?? null;
    if (!is_array($blob)) {
        return ['success' => false, 'message' => 'Bluesky image upload response has no blob.'];
    }

    $image = [
        'alt' => basename($imagePath),
        'image' => $blob,
    ];
    $size = @getimagesize($imagePath);
    if (is_array($size) && isset($size[0], $size[1]) && (int)$size[0] > 0 && (int)$size[1] > 0) {
        $image['aspectRatio'] = ['width' => (int)$size[0], 'height' => (int)$size[1]];
    }

    return [
        'success' => true,
        'embed' => [
            '$type' => 'app.bsky.embed.images',
            'images' => [$image],
        ],
    ];
}

function mastodonUploadMedia(string $instance, string $token, ?string $imagePath): array
{
    if ($imagePath === null || !is_file($imagePath)) {
        return ['success' => true];
    }

    $mimeType = imageMimeType($imagePath);
    if ($mimeType === null) {
        return ['success' => false, 'message' => 'Mastodon image type is not supported.'];
    }

    $response = httpMultipartRequest('POST', $instance . '/api/v1/media', [
        'Authorization: Bearer ' . $token,
    ], [
        'file' => new CURLFile($imagePath, $mimeType, basename($imagePath)),
    ]);
    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? '');
    if ($id === '') {
        return ['success' => false, 'message' => 'Mastodon media upload response has no ID.'];
    }

    return ['success' => true, 'id' => $id];
}

function misskeyUploadFile(string $instance, string $token, ?string $imagePath): array
{
    if ($imagePath === null || !is_file($imagePath)) {
        return ['success' => true];
    }

    $mimeType = imageMimeType($imagePath);
    if ($mimeType === null) {
        return ['success' => false, 'message' => 'Misskey image type is not supported.'];
    }

    $response = httpMultipartRequest('POST', $instance . '/api/drive/files/create', [], [
        'i' => $token,
        'file' => new CURLFile($imagePath, $mimeType, basename($imagePath)),
    ]);
    if (!$response['success']) {
        return $response;
    }

    $id = (string)($response['body']['id'] ?? '');
    if ($id === '') {
        return ['success' => false, 'message' => 'Misskey file upload response has no ID.'];
    }

    return ['success' => true, 'id' => $id];
}

function imageMimeType(string $imagePath): ?string
{
    $mimeType = function_exists('mime_content_type') ? mime_content_type($imagePath) : false;
    if (is_string($mimeType) && imageExtensionFromMime($mimeType) !== null) {
        return $mimeType;
    }

    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    return [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
    ][$extension] ?? null;
}

function blueskyUrlFromUri(string $uri, string $handle): string
{
    $parts = explode('/', $uri);
    $rkey = end($parts);
    return 'https://bsky.app/profile/' . rawurlencode($handle) . '/post/' . rawurlencode((string)$rkey);
}

function httpRawRequest(string $method, string $url, array $headers, string $body): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL extension is not enabled.'];
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => $body,
    ]);

    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($raw === false) {
        return ['success' => false, 'message' => 'API request failed: ' . $error];
    }

    $decoded = json_decode((string)$raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string)$raw;
        return ['success' => false, 'message' => 'API returned an error. HTTP ' . $status . ' ' . $detail];
    }
    if (!is_array($decoded)) {
        return ['success' => false, 'message' => 'API response is not JSON.'];
    }

    return ['success' => true, 'body' => $decoded];
}

function httpMultipartRequest(string $method, string $url, array $headers, array $payload): array
{
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL extension is not enabled.'];
    }

    $payloadInfo = [];
    foreach ($payload as $key => $value) {
        if ($value instanceof CURLFile) {
            $payloadInfo[$key] = [
                'file' => $value->getPostFilename(),
                'mime' => $value->getMimeType(),
            ];
        } else {
            $payloadInfo[$key] = gettype($value);
        }
    }

    socialDebugLog('multipart request start', [
        'method' => $method,
        'url' => $url,
        'payload' => $payloadInfo,
    ]);

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $raw = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    $errno = curl_errno($curl);
    curl_close($curl);

    socialDebugLog('multipart response', [
        'status' => $status,
        'errno' => $errno,
        'error' => $error,
        'raw' => is_string($raw) ? mb_substr($raw, 0, 1000, 'UTF-8') : null,
    ]);

    if ($raw === false) {
        return ['success' => false, 'message' => 'API request failed: ' . $error];
    }

    $decoded = json_decode((string)$raw, true);
    if ($status < 200 || $status >= 300) {
        $detail = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE) : (string)$raw;
        return ['success' => false, 'message' => 'API returned an error. HTTP ' . $status . ' ' . $detail];
    }
    if (!is_array($decoded)) {
        return ['success' => false, 'message' => 'API response is not JSON.'];
    }

    return ['success' => true, 'body' => $decoded];
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
            $curlHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            $body = formEncodedBody($payload);
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

function formEncodedBody(array $payload): string
{
    $pairs = [];
    foreach ($payload as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $item) {
                $pairs[] = rawurlencode((string)$key . '[]') . '=' . rawurlencode((string)$item);
            }
            continue;
        }
        $pairs[] = rawurlencode((string)$key) . '=' . rawurlencode((string)$value);
    }

    return implode('&', $pairs);
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

    $stmt = $pdo->prepare('SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.id = :id AND p.deleted_at IS NULL LIMIT 1');
    bindBoardStatTexts($stmt, $pdo);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
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
    $user = optionalUser($pdo);

    if ($id === false || $id === null || $name === '' || $title === '' || $message === '') {
        jsonResponse(['success' => false, 'message' => '投稿ID・名前・タイトル・本文は必須です。'], 400);
    }

    $post = findActivePostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }
    if (isPresetCommentPost($pdo, $post)) {
        jsonResponse(['success' => false, 'message' => '定型コメントは編集できません。'], 403);
    }

    $isOwner = $user && isset($post['user_id']) && (int)$post['user_id'] === (int)$user['id'];
    if (!$isOwner && ($password === '' || !password_verify($password, $post['password_hash']))) {
        jsonResponse(['success' => false, 'message' => 'パスワードが一致しません。'], 403);
    }

    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $gdgdEnabled = toBoolFlag($config['gdgdEnabled'] ?? false);
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
        $newImage = saveUploadedImage($_FILES['file'], $id, allowedImageExtensionsFromConfig($config));
        if ($newImage === null) {
            jsonResponse(['success' => false, 'message' => '画像のアップロードに失敗しました。'], 400);
        }
        $imagePath = $newImage;
    }

    $tweetText = $post['tweet_text'] ?? null;

    $revisedAt = currentTimestamp();
    $pdo->beginTransaction();
    try {
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
            ':tweet_like_count' => normalizeCount($post['tweet_like_count'] ?? 0),
            ':tweet_retweet_count' => normalizeCount($post['tweet_retweet_count'] ?? 0),
            ':tweet_comment_count' => normalizeCount($post['tweet_comment_count'] ?? 0),
            ':tweet_impression_count' => normalizeCount($post['tweet_impression_count'] ?? 0),
        ]);
        $revisionStmt = $pdo->prepare('INSERT INTO post_revisions (post_id, revised_at) VALUES (:post_id, :revised_at)');
        $revisionStmt->execute([':post_id' => $id, ':revised_at' => $revisedAt]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    $responseMessage = '投稿を更新しました。';

    jsonResponse(['success' => true, 'message' => $responseMessage]);
}

function deletePost(PDO $pdo): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $password = $_POST['password'] ?? '';
    $user = optionalUser($pdo);

    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿IDが不正です。'], 400);
    }

    $post = findActivePostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    $isOwner = $user && isset($post['user_id']) && (int)$post['user_id'] === (int)$user['id'];
    $isPresetComment = isPresetCommentPost($pdo, $post);
    $passwordMatches = $isPresetComment
        ? adminPasswordMatches($pdo, (string)$password)
        : ($password !== '' && password_verify($password, $post['password_hash']));
    if (!$isOwner && !$passwordMatches) {
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
    $sql = 'SELECT * FROM posts WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $posts = array_map(
        fn (array $row): array => buildDeletedPost($pdo, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    jsonResponse(array_merge(deletedNumberGapPosts($pdo, $posts), $posts));
}

function deletedNumberGapPosts(PDO $pdo, array $deletedPosts): array
{
    $firstActive = $pdo->query('SELECT id FROM posts WHERE parent_id = 0 AND deleted_at IS NULL ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($firstActive === false) {
        return [];
    }

    $firstActiveDisplayNo = threadDisplayNo($pdo, (int)$firstActive);
    if ($firstActiveDisplayNo <= 1) {
        return [];
    }

    $knownDeletedRootNumbers = [];
    foreach ($deletedPosts as $post) {
        if ((int)($post['parent_id'] ?? 0) === 0) {
            $knownDeletedRootNumbers[(int)($post['display_no'] ?? 0)] = true;
        }
    }

    $placeholders = [];
    for ($displayNo = 1; $displayNo < $firstActiveDisplayNo; $displayNo++) {
        if (isset($knownDeletedRootNumbers[$displayNo])) {
            continue;
        }
        $placeholders[] = deletedNumberGapPost($displayNo);
    }

    return $placeholders;
}

function deletedNumberGapPost(int $displayNo): array
{
    return [
        'id' => -$displayNo,
        'thread_id' => -$displayNo,
        'parent_id' => 0,
        'name' => 'system',
        'url' => null,
        'title' => '番号だけ残っている削除データ',
        'message' => '投稿データは残っていません。番号ごと消去すると、後続の投稿番号が前詰めされます。',
        'image_path' => null,
        'created_at' => '',
        'deleted_at' => '',
        'gdgd' => false,
        'tweet_off' => true,
        'tweet_text' => null,
        'tweet_url' => null,
        'user_id' => null,
        'user_icon_path' => null,
        'user_display_name' => null,
        'view_count' => 0,
        'revision_count' => 0,
        'revision_dates' => [],
        'board_reactions' => ['views' => 0, 'eejanaika' => 0, 'omigoto' => 0, 'goodjob' => 0],
        'social_links' => [],
        'social_reactions' => [],
        'display_no' => $displayNo,
        'number_gap' => true,
    ];
}

function listAnalyticsPosts(PDO $pdo): void
{
    requireAdmin();
    $stmt = $pdo->prepare('SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.parent_id = 0 AND p.deleted_at IS NULL ORDER BY p.created_at ASC');
    bindBoardStatTexts($stmt, $pdo);
    $stmt->execute();
    $posts = array_map('buildPost', $stmt->fetchAll(PDO::FETCH_ASSOC));

    $accessStmt = $pdo->query('SELECT access_date, count FROM access_counts ORDER BY access_date ASC');
    $accessRows = array_map(
        fn (array $row): array => [
            'id' => -abs((int)str_replace('-', '', (string)$row['access_date'])),
            'thread_id' => 0,
            'parent_id' => 0,
            'name' => 'access',
            'title' => 'access',
            'message' => '',
            'created_at' => (string)$row['access_date'] . ' 00:00:00',
            'gdgd' => false,
            'tweet_off' => true,
            'user_id' => null,
            'user_icon_path' => null,
            'view_count' => 0,
            'access_count' => (int)$row['count'],
            'analytics_kind' => 'access',
            'board_reactions' => ['views' => 0, 'eejanaika' => 0, 'omigoto' => 0, 'goodjob' => 0],
            'social_links' => [],
            'social_reactions' => [],
        ],
        $accessStmt->fetchAll(PDO::FETCH_ASSOC)
    );

    jsonResponse(array_merge($posts, $accessRows));
}

function recordPostView(PDO $pdo): void
{
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿IDが不正です。'], 400);
    }

    $stmt = $pdo->prepare('UPDATE posts SET view_count = view_count + 1 WHERE id = :id AND parent_id = 0 AND deleted_at IS NULL');
    $stmt->execute([':id' => $id]);
    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    $select = $pdo->prepare('SELECT view_count FROM posts WHERE id = :id');
    $select->execute([':id' => $id]);
    jsonResponse(['success' => true, 'view_count' => (int)$select->fetchColumn()]);
}

function listBoardAnalyticsPosts(PDO $pdo): void
{
    $stmt = $pdo->prepare('SELECT ' . postSelectWithBoardStats('p') . ' FROM posts p WHERE p.parent_id = 0 AND p.deleted_at IS NULL ORDER BY p.id ASC');
    bindBoardStatTexts($stmt, $pdo);
    $stmt->execute();

    jsonResponse(array_map(
        fn (array $row): array => withDisplayNo($pdo, buildPost($row), (int)$row['id']),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    ));
}

function listRankingPosts(PDO $pdo): void
{
    $metric = (string)($_GET['metric'] ?? 'views');
    $orderExpression = rankingMetricOrderExpression($metric);
    $sql = 'SELECT ' . postSelectWithBoardStats('p') . '
            FROM posts p
            WHERE p.parent_id = 0 AND p.deleted_at IS NULL
            ORDER BY ' . $orderExpression . ' DESC, p.id DESC
            LIMIT 100';
    $stmt = $pdo->prepare($sql);
    bindBoardStatTexts($stmt, $pdo);
    $stmt->execute();

    jsonResponse(array_map(
        fn (array $row): array => withDisplayNo($pdo, buildPost($row), (int)$row['id']),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    ));
}

function rankingMetricOrderExpression(string $metric): string
{
    return match ($metric) {
        'comments' => 'reply_count',
        'eejanaika' => 'eejanaika_count',
        'omigoto' => 'omigoto_count',
        'goodjob' => 'goodjob_count',
        'xLikes' => 'p.tweet_like_count',
        'xReposts' => 'p.tweet_retweet_count',
        'xImpressions' => 'p.tweet_impression_count',
        'blueskyLikes' => 'p.bluesky_like_count',
        'mastodonFavs' => 'p.mastodon_fav_count',
        'misskeyReactions' => '(p.misskey_fire_count + p.misskey_eyes_count + p.misskey_cry_count + p.misskey_thinking_count + p.misskey_party_count + p.misskey_other_count)',
        default => 'p.view_count',
    };
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

function purgePostData(PDO $pdo): void
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

    $pdo->beginTransaction();
    try {
        if ((int)$post['thread_id'] === $id) {
            eraseThreadDataKeepingNumber($pdo, $id);
        } else {
            eraseReplyDataKeepingNumber($pdo, $id);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    jsonResponse(['success' => true, 'message' => '投稿データを消去しました。表示番号は保持されています。']);
}

function destroyPostNumber(PDO $pdo): void
{
    requireAdmin();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => '投稿IDが不正です。'], 400);
    }

    if ($id < 0) {
        destroyDisplayNumberGap($pdo, abs($id));
        jsonResponse(['success' => true, 'message' => 'Post number purged.']);
    }

    $post = findPostById($pdo, $id);
    if (!$post) {
        jsonResponse(['success' => false, 'message' => '投稿が見つかりません。'], 404);
    }

    $pdo->beginTransaction();
    try {
        if ((int)$post['thread_id'] === $id) {
            permanentlyDeleteThread($pdo, $id);
        } else {
            permanentlyDeletePosts($pdo, [$id]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    jsonResponse(['success' => true, 'message' => '投稿番号ごと完全削除しました。']);
}

function destroyDisplayNumberGap(PDO $pdo, int $displayNo): void
{
    $targetId = postIdFromDisplayNo($pdo, $displayNo);
    if ($targetId === null) {
        return;
    }

    $post = findPostById($pdo, $targetId);
    if (!$post || (int)$post['parent_id'] !== 0) {
        return;
    }

    if (($post['deleted_at'] ?? null) === null) {
        jsonResponse(['success' => false, 'message' => '表示中の投稿は番号だけ消去できません。先に一括削除してください。'], 409);
    }

    $pdo->beginTransaction();
    try {
        permanentlyDeleteThread($pdo, $targetId);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
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

    $configured = getenv('THREADFORGE_ADMIN_PASSWORD') ?: '';
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

function listAdminUsers(PDO $pdo): void
{
    requireAdmin();
    $stmt = $pdo->prepare(
        'SELECT u.*,
                (SELECT COUNT(*) FROM posts p WHERE p.user_id = u.id) AS post_count,
                (SELECT COUNT(*) FROM user_post_claims c WHERE c.user_id = u.id) AS claim_count,
                (SELECT COUNT(*) FROM user_sessions s WHERE s.user_id = u.id AND s.expires_at > :now) AS active_session_count,
                (SELECT MAX(s.created_at) FROM user_sessions s WHERE s.user_id = u.id) AS last_session_at
         FROM users u
         ORDER BY u.id ASC'
    );
    $stmt->execute([':now' => currentTimestamp()]);
    $users = array_map(static fn(array $row): array => [
        'id' => (int)$row['id'],
        'login_id' => (string)$row['login_id'],
        'display_name' => (string)$row['display_name'],
        'post_password' => (string)$row['post_password'],
        'home_url' => $row['home_url'] ?? '',
        'icon_path' => publicStoragePath($row['icon_path'] ?? null),
        'created_at' => (string)$row['created_at'],
        'updated_at' => (string)$row['updated_at'],
        'post_count' => (int)$row['post_count'],
        'claim_count' => (int)$row['claim_count'],
        'last_login_at' => $row['last_login_at'] ?? ($row['last_session_at'] ?? null),
        'last_session_at' => $row['last_session_at'] ?? null,
        'active_session_count' => (int)($row['active_session_count'] ?? 0),
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));

    jsonResponse(['success' => true, 'users' => $users]);
}

function adminUpdateUser(PDO $pdo): void
{
    requireAdmin();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => 'ユーザーIDが不正です。'], 400);
    }
    $user = findUserById($pdo, $id);
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'ユーザーが見つかりません。'], 404);
    }

    $loginId = trim((string)($_POST['login_id'] ?? $user['login_id']));
    $displayName = trim((string)($_POST['display_name'] ?? $user['display_name']));
    $postPassword = trim((string)($_POST['post_password'] ?? $user['post_password']));
    $homeUrl = normalizeUrl($_POST['home_url'] ?? ($user['home_url'] ?? null));
    $newPassword = trim((string)($_POST['login_password'] ?? ''));

    if ($loginId === '' || $displayName === '') {
        jsonResponse(['success' => false, 'message' => 'IDと名前を入力してください。'], 400);
    }
    if (mb_strlen($displayName) > 30) {
        jsonResponse(['success' => false, 'message' => '名前は30文字以内で入力してください。'], 400);
    }
    if (strlen($postPassword) > 8) {
        $postPassword = substr($postPassword, 0, 8);
    }

    $duplicate = findUserByLoginId($pdo, $loginId);
    if ($duplicate && (int)$duplicate['id'] !== $id) {
        jsonResponse(['success' => false, 'message' => 'このIDは既に使われています。'], 409);
    }

    $fields = 'login_id = :login_id, display_name = :display_name, post_password = :post_password, home_url = :home_url, updated_at = :updated_at';
    $params = [
        ':id' => $id,
        ':login_id' => $loginId,
        ':display_name' => $displayName,
        ':post_password' => $postPassword,
        ':home_url' => $homeUrl,
        ':updated_at' => currentTimestamp(),
    ];
    if ($newPassword !== '') {
        $fields .= ', password_hash = :password_hash';
        $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }
    $stmt = $pdo->prepare('UPDATE users SET ' . $fields . ' WHERE id = :id');
    $stmt->execute($params);

    jsonResponse(['success' => true, 'message' => 'ユーザー情報を更新しました。']);
}

function adminDeleteUser(PDO $pdo): void
{
    requireAdmin();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $stage = filter_input(INPUT_POST, 'stage', FILTER_VALIDATE_INT) ?: 1;
    if ($id === false || $id === null) {
        jsonResponse(['success' => false, 'message' => 'ユーザーIDが不正です。'], 400);
    }
    $user = findUserById($pdo, $id);
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'ユーザーが見つかりません。'], 404);
    }

    $pdo->beginTransaction();
    try {
        if ($stage >= 2) {
            deleteStorageFile($user['icon_path'] ?? null);
            $pdo->prepare('UPDATE posts SET user_id = NULL WHERE user_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM user_post_claims WHERE user_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $id]);
            compactUserIds($pdo);
            $message = 'ユーザー番号ごと完全削除しました。';
        } else {
            deleteStorageFile($user['icon_path'] ?? null);
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET login_id = :login_id,
                     password_hash = :password_hash,
                     display_name = :display_name,
                     post_password = "",
                     home_url = NULL,
                     icon_path = NULL,
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id' => $id,
                ':login_id' => 'deleted_user_' . $id,
                ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ':display_name' => '削除済みユーザー',
                ':updated_at' => currentTimestamp(),
            ]);
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id = :id')->execute([':id' => $id]);
            $message = 'ユーザー情報を消去しました。ユーザー番号は保持されています。';
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    jsonResponse(['success' => true, 'message' => $message]);
}

function eraseThreadDataKeepingNumber(PDO $pdo, int $threadId): void
{
    $stmt = $pdo->prepare('SELECT id, image_path FROM posts WHERE thread_id = :thread_id AND id != :thread_id');
    $stmt->execute([':thread_id' => $threadId]);
    $replyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $replyIds = array_map(static fn(array $row): int => (int)$row['id'], $replyRows);
    foreach ($replyRows as $row) {
        deleteStorageFile($row['image_path'] ?? null);
    }
    if ($replyIds !== []) {
        permanentlyDeletePosts($pdo, $replyIds, false);
    }

    $stmt = $pdo->prepare('SELECT image_path FROM posts WHERE id = :id');
    $stmt->execute([':id' => $threadId]);
    deleteStorageFile($stmt->fetchColumn() ?: null);
    $update = $pdo->prepare(
        'UPDATE posts
         SET name = "消去済み",
             url = NULL,
             title = "消去済み",
             message = "この投稿は消去されました。",
             image_path = NULL,
             password_hash = :password_hash,
             gdgd = 0,
             tweet_off = 1,
             tweet_text = NULL,
             tweet_url = NULL,
             tweet_like_count = 0,
             tweet_retweet_count = 0,
             tweet_comment_count = 0,
             tweet_impression_count = 0,
             bluesky_uri = NULL,
             bluesky_cid = NULL,
             bluesky_url = NULL,
             bluesky_like_count = 0,
             bluesky_repost_count = 0,
             bluesky_quote_count = 0,
             mastodon_id = NULL,
             mastodon_url = NULL,
             mastodon_boost_count = 0,
             mastodon_fav_count = 0,
             misskey_id = NULL,
             misskey_url = NULL,
             misskey_fire_count = 0,
             misskey_eyes_count = 0,
             misskey_cry_count = 0,
             misskey_thinking_count = 0,
             misskey_party_count = 0,
             misskey_other_count = 0,
             user_id = NULL,
             view_count = 0,
             deleted_at = COALESCE(deleted_at, :deleted_at)
         WHERE id = :id'
    );
    $update->execute([
        ':id' => $threadId,
        ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ':deleted_at' => currentTimestamp(),
    ]);
    $pdo->prepare('DELETE FROM post_revisions WHERE post_id = :id')->execute([':id' => $threadId]);
    $pdo->prepare('DELETE FROM user_post_claims WHERE post_id = :id')->execute([':id' => $threadId]);
}

function eraseReplyDataKeepingNumber(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('SELECT image_path FROM posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    deleteStorageFile($stmt->fetchColumn() ?: null);
    $update = $pdo->prepare(
        'UPDATE posts
         SET name = "消去済み",
             url = NULL,
             title = "消去済み",
             message = "この返信は消去されました。",
             image_path = NULL,
             password_hash = :password_hash,
             user_id = NULL,
             deleted_at = COALESCE(deleted_at, :deleted_at)
         WHERE id = :id'
    );
    $update->execute([
        ':id' => $id,
        ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ':deleted_at' => currentTimestamp(),
    ]);
    $pdo->prepare('DELETE FROM post_revisions WHERE post_id = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM user_post_claims WHERE post_id = :id')->execute([':id' => $id]);
}

function permanentlyDeleteThread(PDO $pdo, int $threadId): void
{
    $stmt = $pdo->prepare('SELECT id FROM posts WHERE thread_id = :thread_id');
    $stmt->execute([':thread_id' => $threadId]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    permanentlyDeletePosts($pdo, $ids, true);
}

function permanentlyDeletePosts(PDO $pdo, array $ids, bool $deleteImages = true): void
{
    $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
    if ($ids === []) {
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    if ($deleteImages) {
        $stmt = $pdo->prepare('SELECT image_path FROM posts WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
            deleteStorageFile($path ?: null);
        }
    }
    $pdo->prepare('DELETE FROM post_revisions WHERE post_id IN (' . $placeholders . ')')->execute($ids);
    $pdo->prepare('DELETE FROM user_post_claims WHERE post_id IN (' . $placeholders . ')')->execute($ids);
    $pdo->prepare('DELETE FROM posts WHERE id IN (' . $placeholders . ')')->execute($ids);
}

function deleteStorageFile(mixed $path): void
{
    if (!is_string($path) || $path === '') {
        return;
    }
    $basename = backupImageBasename($path);
    $storagePath = STORAGE_DIR . '/' . $basename;
    if (is_file($storagePath)) {
        @unlink($storagePath);
    }
}

function compactUserIds(PDO $pdo): void
{
    $users = $pdo->query('SELECT id FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
    $map = [];
    $next = 1;
    foreach ($users as $oldId) {
        $old = (int)$oldId;
        $map[$old] = $next++;
    }
    foreach (array_keys($map) as $oldId) {
        $temporary = -abs((int)$oldId);
        $pdo->prepare('UPDATE users SET id = :new WHERE id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
        $pdo->prepare('UPDATE posts SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
        $pdo->prepare('UPDATE user_sessions SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
        $pdo->prepare('UPDATE user_post_claims SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
    }
    foreach ($map as $oldId => $newId) {
        $temporary = -abs((int)$oldId);
        $pdo->prepare('UPDATE users SET id = :new WHERE id = :old')->execute([':new' => $newId, ':old' => $temporary]);
        $pdo->prepare('UPDATE posts SET user_id = :new WHERE user_id = :old')->execute([':new' => $newId, ':old' => $temporary]);
        $pdo->prepare('UPDATE user_sessions SET user_id = :new WHERE user_id = :old')->execute([':new' => $newId, ':old' => $temporary]);
        $pdo->prepare('UPDATE user_post_claims SET user_id = :new WHERE user_id = :old')->execute([':new' => $newId, ':old' => $temporary]);
    }
    $seq = count($map);
    $pdo->prepare("UPDATE sqlite_sequence SET seq = :seq WHERE name = 'users'")->execute([':seq' => $seq]);
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
        'message' => 'DBを確認しました。現行版にシステムインデックス修復は不要です。',
        'orphan_replies' => $orphanReplies,
        'missing_image_post_ids' => $missingImages,
    ]);
}

function renumberPostsByCreatedAt(PDO $pdo): void
{
    requireAdmin();

    $pdo->beginTransaction();
    try {
        $result = renumberPostIdsByCreatedAt($pdo);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    jsonResponse([
        'success' => true,
        'message' => '投稿番号を投稿日順に採番しなおしました。',
        'renumbered_posts' => $result['renumbered_posts'],
        'renumbered_threads' => $result['renumbered_threads'],
    ]);
}

function renumberPostIdsByCreatedAt(PDO $pdo): array
{
    $roots = $pdo->query('SELECT id FROM posts WHERE parent_id = 0 ORDER BY created_at ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
    if ($roots === []) {
        return ['renumbered_posts' => 0, 'renumbered_threads' => 0];
    }

    $replyStmt = $pdo->prepare('SELECT id FROM posts WHERE parent_id != 0 AND thread_id = :thread_id ORDER BY created_at ASC, id ASC');
    $orderedIds = [];
    foreach ($roots as $rootId) {
        $rootId = (int)$rootId;
        $orderedIds[] = $rootId;
        $replyStmt->execute([':thread_id' => $rootId]);
        foreach ($replyStmt->fetchAll(PDO::FETCH_COLUMN) as $replyId) {
            $orderedIds[] = (int)$replyId;
        }
    }
    $knownIds = array_fill_keys($orderedIds, true);
    $allIds = $pdo->query('SELECT id FROM posts ORDER BY created_at ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allIds as $postId) {
        $postId = (int)$postId;
        if (!isset($knownIds[$postId])) {
            $orderedIds[] = $postId;
        }
    }

    $idMap = [];
    $nextId = 1;
    foreach ($orderedIds as $oldId) {
        $idMap[$oldId] = $nextId++;
    }

    $postRows = $pdo->query('SELECT id, thread_id, parent_id, image_path FROM posts ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    $imageMap = prepareRenumberedPostImages($postRows, $idMap);

    foreach ($postRows as $row) {
        $oldId = (int)$row['id'];
        $tempId = -$oldId;
        $tempThreadId = -((int)$row['thread_id']);
        $parentId = (int)$row['parent_id'];
        $tempParentId = $parentId === 0 ? 0 : -$parentId;
        $pdo->prepare('UPDATE posts SET id = :new_id, thread_id = :thread_id, parent_id = :parent_id WHERE id = :old_id')
            ->execute([
                ':new_id' => $tempId,
                ':thread_id' => $tempThreadId,
                ':parent_id' => $tempParentId,
                ':old_id' => $oldId,
            ]);
    }

    $pdo->exec('UPDATE post_revisions SET post_id = -post_id');
    $pdo->exec('UPDATE user_post_claims SET post_id = -post_id');

    foreach ($postRows as $row) {
        $oldId = (int)$row['id'];
        $newId = $idMap[$oldId] ?? $oldId;
        $newThreadId = $idMap[(int)$row['thread_id']] ?? (int)$row['thread_id'];
        $oldParentId = (int)$row['parent_id'];
        $newParentId = $oldParentId === 0 ? 0 : ($idMap[$oldParentId] ?? $oldParentId);
        $imagePath = $imageMap[$oldId] ?? ($row['image_path'] ?? null);

        $pdo->prepare('UPDATE posts SET id = :new_id, thread_id = :thread_id, parent_id = :parent_id, image_path = :image_path WHERE id = :old_id')
            ->execute([
                ':new_id' => $newId,
                ':thread_id' => $newThreadId,
                ':parent_id' => $newParentId,
                ':image_path' => $imagePath,
                ':old_id' => -$oldId,
            ]);
    }

    foreach ($idMap as $oldId => $newId) {
        $temporary = -$oldId;
        $pdo->prepare('UPDATE post_revisions SET post_id = :new_id WHERE post_id = :old_id')->execute([':new_id' => $newId, ':old_id' => $temporary]);
        $pdo->prepare('UPDATE user_post_claims SET post_id = :new_id WHERE post_id = :old_id')->execute([':new_id' => $newId, ':old_id' => $temporary]);
    }

    $maxId = count($idMap);
    $updated = $pdo->prepare("UPDATE sqlite_sequence SET seq = :seq WHERE name = 'posts'");
    $updated->execute([':seq' => $maxId]);
    if ($updated->rowCount() === 0) {
        $pdo->prepare("INSERT INTO sqlite_sequence (name, seq) VALUES ('posts', :seq)")->execute([':seq' => $maxId]);
    }

    return [
        'renumbered_posts' => count($idMap),
        'renumbered_threads' => count($roots),
    ];
}

function prepareRenumberedPostImages(array $postRows, array $idMap): array
{
    $moves = [];
    foreach ($postRows as $row) {
        $oldId = (int)$row['id'];
        $newId = (int)($idMap[$oldId] ?? $oldId);
        $path = $row['image_path'] ?? null;
        if (!is_string($path) || $path === '') {
            continue;
        }
        $basename = backupImageBasename($path);
        if ($basename === '') {
            continue;
        }
        $current = STORAGE_DIR . '/' . $basename;
        if (!is_file($current)) {
            continue;
        }
        $extension = pathinfo($basename, PATHINFO_EXTENSION);
        if ($extension === '') {
            continue;
        }
        $stem = pathinfo($basename, PATHINFO_FILENAME);
        if ($stem !== (string)$oldId) {
            continue;
        }
        $temporary = STORAGE_DIR . '/renumber_' . $oldId . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (@rename($current, $temporary)) {
            $moves[] = ['old_id' => $oldId, 'new_id' => $newId, 'temporary' => $temporary, 'extension' => $extension];
        }
    }

    $imageMap = [];
    foreach ($moves as $move) {
        $target = STORAGE_DIR . '/' . $move['new_id'] . '.' . $move['extension'];
        if (is_file($target)) {
            $target = STORAGE_DIR . '/' . $move['new_id'] . '_' . bin2hex(random_bytes(4)) . '.' . $move['extension'];
        }
        if (@rename($move['temporary'], $target)) {
            $imageMap[(int)$move['old_id']] = $target;
        }
    }
    return $imageMap;
}

function refreshSocialReactions(PDO $pdo): void
{
    requireAdmin();
    $result = runSocialReactionRefresh($pdo);

    jsonResponse([
        'success' => true,
        'message' => 'SNSリアクションを更新しました。',
        'updated' => $result['updated'],
        'errors' => $result['errors'],
    ]);
}

function cronRefreshSocialReactions(PDO $pdo): void
{
    requireCronApiKey($pdo);
    $result = runSocialReactionRefresh($pdo);

    jsonResponse([
        'success' => true,
        'message' => 'SNSリアクションを更新しました。',
        'updated' => $result['updated'],
        'checked_posts' => $result['checked_posts'],
        'recent_days' => $result['recent_days'],
        'errors' => $result['errors'],
    ]);
}

function requireCronApiKey(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $expected = (string)($settings['security']['cronApiKey'] ?? '');
    $provided = (string)($_GET['api_key'] ?? $_POST['api_key'] ?? '');

    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        jsonResponse(['success' => false, 'message' => 'Cron APIキーが無効です。'], 403);
    }
}

function runSocialReactionRefresh(PDO $pdo): array
{
    $settings = loadSettings($pdo);
    $stmt = $pdo->prepare(
        "SELECT * FROM posts
         WHERE deleted_at IS NULL
           AND parent_id = 0
           AND (
             tweet_url IS NOT NULL OR bluesky_uri IS NOT NULL OR mastodon_id IS NOT NULL OR misskey_id IS NOT NULL
           )
         ORDER BY id ASC"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    return [
        'updated' => $updated,
        'errors' => $errors,
        'checked_posts' => count($rows),
        'recent_days' => null,
    ];
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
    exportSqliteBackup($pdo);
}

function exportSqliteBackup(PDO $pdo): void
{
    $zipPath = tempnam(sys_get_temp_dir(), 'threadforge-backup-');
    if ($zipPath === false) {
        jsonResponse(['success' => false, 'message' => 'バックアップZIPを作成できませんでした。'], 500);
    }
    @unlink($zipPath);
    $zipPath .= '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        jsonResponse(['success' => false, 'message' => 'バックアップZIPを作成できませんでした。'], 500);
    }

    $manifest = [
        'backup_version' => 3,
        'backup_format' => 'sqlite-zip',
        'exported_at' => currentTimestamp(),
    ];
    $zip->addFromString('backup.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    if (is_file(DB_FILE)) {
        $zip->addFile(DB_FILE, 'database.sqlite');
    }
    $addedImages = [];
    if (is_dir(STORAGE_DIR)) {
        foreach (new DirectoryIterator(STORAGE_DIR) as $file) {
            if (!$file->isFile()) {
                continue;
            }
            addBackupZipImageFile($zip, $file->getPathname(), $addedImages);
        }
    }
    addReferencedBackupImages($pdo, $zip, $addedImages);
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="threadforge-full-backup-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    exit;
}

function addReferencedBackupImages(PDO $pdo, ZipArchive $zip, array &$addedImages): void
{
    $queries = [
        'SELECT image_path AS path FROM posts WHERE image_path IS NOT NULL AND image_path <> ""',
        'SELECT icon_path AS path FROM users WHERE icon_path IS NOT NULL AND icon_path <> ""',
    ];
    foreach ($queries as $sql) {
        $stmt = $pdo->query($sql);
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $path = (string)($row['path'] ?? '');
            if ($path === '') {
                continue;
            }
            if (is_file($path)) {
                addBackupZipImageFile($zip, $path, $addedImages);
                continue;
            }
            $storagePath = STORAGE_DIR . '/' . backupImageBasename($path);
            if (is_file($storagePath)) {
                addBackupZipImageFile($zip, $storagePath, $addedImages);
            }
        }
    }
}

function addBackupZipImageFile(ZipArchive $zip, string $path, array &$addedImages): void
{
    $basename = backupImageBasename($path);
    if ($basename === '' || isset($addedImages[$basename]) || !is_file($path)) {
        return;
    }
    $zip->addFile($path, 'images/' . $basename);
    $addedImages[$basename] = true;
}

function importBackup(PDO $pdo): void
{
    requireAdmin();
    if (empty($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'バックアップファイルを選択してください。'], 400);
    }

    $backupPath = (string)$_FILES['backup']['tmp_name'];
    if (isSqliteBackupUpload($backupPath)) {
        $pdo = null;
        restoreSqliteBackup($backupPath);
        jsonResponse(['success' => true, 'message' => 'バックアップをインポートしました。']);
    }

    [$payload, $backupImageContents] = readBackupUpload($backupPath);
    if (!is_array($payload) || !in_array(($payload['backup_version'] ?? null), [1, 2], true) || !isset($payload['posts']) || !is_array($payload['posts'])) {
        jsonResponse(['success' => false, 'message' => 'バックアップ形式が正しくありません。'], 400);
    }

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }
    foreach (glob(STORAGE_DIR . '/*') ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    $backupImages = [];
    foreach ($backupImageContents as $filename => $contents) {
        $basename = backupImageBasename((string)$filename);
        if ($basename === '') {
            continue;
        }
        $backupImages[$basename] = true;
        file_put_contents(STORAGE_DIR . '/' . $basename, $contents);
    }

    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM post_revisions');
    $pdo->exec('DELETE FROM user_post_claims');
    $pdo->exec('DELETE FROM user_sessions');
    $pdo->exec('DELETE FROM posts');
    $pdo->exec('DELETE FROM users');
    $pdo->exec('DELETE FROM access_counts');

    $userColumns = tableColumnNames($pdo, 'users');
    foreach (($payload['users'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row['icon_path'] = importedBackupImagePath($row['icon_path'] ?? null, $backupImages);
        insertBackupRow($pdo, 'users', $userColumns, $row);
    }

    $accessColumns = tableColumnNames($pdo, 'access_counts');
    foreach (($payload['access_counts'] ?? []) as $row) {
        if (is_array($row)) {
            insertBackupRow($pdo, 'access_counts', $accessColumns, $row);
        }
    }

    $postColumns = tableColumnNames($pdo, 'posts');
    foreach ($payload['posts'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row['image_path'] = importedBackupImagePath($row['image_path'] ?? null, $backupImages);
        insertBackupRow($pdo, 'posts', $postColumns, $row);
    }
    $revisionColumns = tableColumnNames($pdo, 'post_revisions');
    foreach (($payload['post_revisions'] ?? []) as $row) {
        if (is_array($row)) {
            insertBackupRow($pdo, 'post_revisions', $revisionColumns, $row);
        }
    }
    $claimColumns = tableColumnNames($pdo, 'user_post_claims');
    foreach (($payload['user_post_claims'] ?? []) as $row) {
        if (is_array($row)) {
            insertBackupRow($pdo, 'user_post_claims', $claimColumns, $row);
        }
    }
    $pdo->commit();

    if (isset($payload['settings']) && is_array($payload['settings'])) {
        saveSettings($pdo, $payload['settings']);
    }

    jsonResponse(['success' => true, 'message' => 'バックアップをインポートしました。']);
}

function readBackupUpload(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) === true) {
        $payloadJson = $zip->getFromName('backup.json');
        $payload = is_string($payloadJson) ? json_decode($payloadJson, true) : null;
        $images = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string)($stat['name'] ?? '') : '';
            if ($name === '' || str_ends_with($name, '/') || !str_starts_with($name, 'images/')) {
                continue;
            }
            $basename = backupImageBasename($name);
            if ($basename === '') {
                continue;
            }
            $contents = $zip->getFromIndex($i);
            if (is_string($contents)) {
                $images[$basename] = $contents;
            }
        }
        $zip->close();
        return [is_array($payload) ? $payload : null, $images];
    }

    $payload = json_decode((string)file_get_contents($path), true);
    $images = [];
    if (is_array($payload) && is_array($payload['images'] ?? null)) {
        foreach ($payload['images'] as $filename => $encoded) {
            $decoded = base64_decode((string)$encoded, true);
            if ($decoded === false) {
                continue;
            }
            $basename = backupImageBasename((string)$filename);
            if ($basename !== '') {
                $images[$basename] = $decoded;
            }
        }
    }

    return [is_array($payload) ? $payload : null, $images];
}

function isSqliteBackupUpload(string $path): bool
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return false;
    }
    $payloadJson = $zip->getFromName('backup.json');
    $hasDatabase = $zip->locateName('database.sqlite') !== false;
    $zip->close();
    if (!is_string($payloadJson) || !$hasDatabase) {
        return false;
    }
    $payload = json_decode($payloadJson, true);
    return is_array($payload) && (int)($payload['backup_version'] ?? 0) === 3;
}

function restoreSqliteBackup(string $path): void
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        jsonResponse(['success' => false, 'message' => 'バックアップZIPを開けませんでした。'], 400);
    }

    $databaseStream = $zip->getStream('database.sqlite');
    if ($databaseStream === false) {
        $zip->close();
        jsonResponse(['success' => false, 'message' => 'バックアップ内にDBがありません。'], 400);
    }

    $tempDb = tempnam(sys_get_temp_dir(), 'threadforge-db-');
    if ($tempDb === false) {
        fclose($databaseStream);
        $zip->close();
        jsonResponse(['success' => false, 'message' => '一時DBを作成できませんでした。'], 500);
    }
    $tempOut = fopen($tempDb, 'wb');
    if ($tempOut === false) {
        fclose($databaseStream);
        $zip->close();
        @unlink($tempDb);
        jsonResponse(['success' => false, 'message' => '一時DBを書き込めませんでした。'], 500);
    }
    stream_copy_to_stream($databaseStream, $tempOut);
    fclose($databaseStream);
    fclose($tempOut);

    try {
        $test = new PDO('sqlite:' . $tempDb);
        $test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $test->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        $test = null;
    } catch (Throwable) {
        $zip->close();
        @unlink($tempDb);
        jsonResponse(['success' => false, 'message' => 'バックアップDBの形式が正しくありません。'], 400);
    }

    if (!is_dir(dirname(DB_FILE))) {
        mkdir(dirname(DB_FILE), 0775, true);
    }
    if (!copy($tempDb, DB_FILE)) {
        $zip->close();
        @unlink($tempDb);
        jsonResponse(['success' => false, 'message' => 'DBを復元できませんでした。'], 500);
    }
    @unlink($tempDb);

    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0775, true);
    }
    foreach (glob(STORAGE_DIR . '/*') ?: [] as $current) {
        if (is_file($current)) {
            @unlink($current);
        }
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $name = is_array($stat) ? (string)($stat['name'] ?? '') : '';
        if ($name === '' || str_ends_with($name, '/') || !str_starts_with($name, 'images/')) {
            continue;
        }
        $basename = backupImageBasename($name);
        if ($basename === '') {
            continue;
        }
        $imageStream = $zip->getStream($name);
        if ($imageStream === false) {
            continue;
        }
        $imageOut = fopen(STORAGE_DIR . '/' . $basename, 'wb');
        if ($imageOut !== false) {
            stream_copy_to_stream($imageStream, $imageOut);
            fclose($imageOut);
        }
        fclose($imageStream);
    }
    $zip->close();
    normalizeRestoredSqliteImagePaths();
}

function normalizeRestoredSqliteImagePaths(): void
{
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $targets = [
        ['table' => 'posts', 'column' => 'image_path'],
        ['table' => 'users', 'column' => 'icon_path'],
    ];
    foreach ($targets as $target) {
        $table = $target['table'];
        $column = $target['column'];
        $select = $pdo->query("SELECT id, {$column} AS path FROM {$table} WHERE {$column} IS NOT NULL AND {$column} <> ''");
        $update = $pdo->prepare("UPDATE {$table} SET {$column} = :path WHERE id = :id");
        while (($row = $select->fetch(PDO::FETCH_ASSOC)) !== false) {
            $basename = backupImageBasename((string)($row['path'] ?? ''));
            if ($basename === '' || !is_file(STORAGE_DIR . '/' . $basename)) {
                continue;
            }
            $storagePath = STORAGE_DIR . '/' . $basename;
            if ((string)$row['path'] === $storagePath) {
                continue;
            }
            $update->execute([
                ':path' => $storagePath,
                ':id' => (int)$row['id'],
            ]);
        }
    }
}

function importedBackupImagePath(mixed $imagePath, array $images): ?string
{
    $path = trim((string)$imagePath);
    if ($path === '') {
        return null;
    }
    $basename = backupImageBasename($path);
    if ($basename === '') {
        return null;
    }
    return array_key_exists($basename, $images) ? STORAGE_DIR . '/' . $basename : null;
}

function backupImageBasename(string $path): string
{
    $basename = basename(str_replace('\\', '/', $path));
    return in_array($basename, ['', '.', '..'], true) ? '' : $basename;
}

function tableColumnNames(PDO $pdo, string $table): array
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    return array_map(static fn(array $row): string => (string)$row['name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function insertBackupRow(PDO $pdo, string $table, array $tableColumns, array $row): void
{
    $columns = array_values(array_filter($tableColumns, static fn(string $column): bool => array_key_exists($column, $row)));
    if ($columns === []) {
        return;
    }

    $quotedTable = '"' . str_replace('"', '""', $table) . '"';
    $quotedColumns = array_map(static fn(string $column): string => '"' . str_replace('"', '""', $column) . '"', $columns);
    $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
    $stmt = $pdo->prepare('INSERT INTO ' . $quotedTable . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $params = [];
    foreach ($columns as $column) {
        $params[':' . $column] = $row[$column];
    }
    $stmt->execute($params);
}

function getSettings(PDO $pdo): void
{
    requireAdmin();
    $cronApiKey = ensureCronApiKey($pdo);
    jsonResponse([
        'success' => true,
        'settings' => loadSettings($pdo),
        'system' => [
            'cronPath' => cronScriptPath(),
            'cronApiUrl' => cronApiUrl(),
            'cronApiKey' => $cronApiKey,
            'adminPasswordConfigured' => adminPasswordHash($pdo) !== '',
        ],
    ]);
}

function cronScriptPath(): string
{
    return realpath(__DIR__ . '/cron.php') ?: __DIR__ . '/cron.php';
}

function cronApiUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return 'api.php?action=cronRefreshSocialReactions&api_key=';
    }

    $path = (string)($_SERVER['SCRIPT_NAME'] ?? '/api.php');
    return $scheme . '://' . $host . $path . '?action=cronRefreshSocialReactions&api_key=';
}

function ensureCronApiKey(PDO $pdo): string
{
    $settings = loadSettings($pdo);
    $key = (string)($settings['security']['cronApiKey'] ?? '');
    if ($key !== '') {
        return $key;
    }

    $key = bin2hex(random_bytes(24));
    $settings['security']['cronApiKey'] = $key;
    saveSettings($pdo, $settings);
    return $key;
}

function allowedImageExtensionsFromConfig(array $config): array
{
    $default = ['gif', 'png', 'jpeg', 'jpg', 'bmp'];
    $raw = $config['allowedImageTypes'] ?? $default;
    if (is_string($raw)) {
        $raw = array_map('trim', explode(',', $raw));
    }
    if (!is_array($raw)) {
        return $default;
    }
    $allowed = array_values(array_intersect($default, array_map(
        fn ($value): string => strtolower((string)$value),
        $raw
    )));
    if (in_array('jpeg', $allowed, true) && !in_array('jpg', $allowed, true)) {
        $allowed[] = 'jpg';
    }
    if (in_array('jpg', $allowed, true) && !in_array('jpeg', $allowed, true)) {
        $allowed[] = 'jpeg';
    }
    return $allowed === [] ? $default : $allowed;
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
                'manualTitle' => (string)($config['manualTitle'] ?? 'ThreadForge'),
                'manualBody' => (string)($config['manualBody'] ?? defaultManualBody()),
                'tweetEnabled' => toBoolFlag($config['tweetEnabled'] ?? false),
                'blueskyEnabled' => toBoolFlag($config['blueskyEnabled'] ?? false),
                'mastodonEnabled' => toBoolFlag($config['mastodonEnabled'] ?? false),
                'misskeyEnabled' => toBoolFlag($config['misskeyEnabled'] ?? false),
                'gdgdEnabled' => toBoolFlag($config['gdgdEnabled'] ?? false),
                'gdgdLabel' => (string)($config['gdgdLabel'] ?? '特殊投稿'),
                'eejanaikaOmigotoText' => (string)($config['eejanaikaOmigotoText'] ?? 'お美事にございまする'),
                'eejanaikaOmigotoColor' => (string)($config['eejanaikaOmigotoColor'] ?? '#ff72ff'),
                'eejanaikaGoodjobText' => (string)($config['eejanaikaGoodjobText'] ?? 'いい仕事してますねぇ'),
                'eejanaikaGoodjobColor' => (string)($config['eejanaikaGoodjobColor'] ?? '#27a8ff'),
                'eejanaikaEejanaikaText' => (string)($config['eejanaikaEejanaikaText'] ?? 'ええじゃないか'),
                'eejanaikaEejanaikaColor' => (string)($config['eejanaikaEejanaikaColor'] ?? '#fff200'),
                'socialHashtags' => (string)($config['socialHashtags'] ?? '#art'),
                'allowedImageTypes' => allowedImageExtensionsFromConfig($config),
                'listOrder' => listThreadOrder($settings),
            ],
        ],
    ]);
}

function updateSettings(PDO $pdo): void
{
    requireAdmin();
    $settingsJson = (string)($_POST['settings'] ?? '');
    if (isset($_POST['settings_b64'])) {
        $decodedSettings = base64_decode((string)$_POST['settings_b64'], true);
        if ($decodedSettings === false) {
            jsonResponse(['success' => false, 'message' => '設定データが正しくありません。'], 400);
        }
        $settingsJson = $decodedSettings;
    }
    $settings = json_decode($settingsJson, true);
    if (!is_array($settings)) {
        jsonResponse(['success' => false, 'message' => '設定データが正しくありません。'], 400);
    }
    if (isset($settings['config']) && is_array($settings['config']) && array_key_exists('bbsTitle', $settings['config'])) {
        $settings['config']['manualTitle'] = (string)$settings['config']['bbsTitle'];
    }
    saveSettings($pdo, $settings);
    jsonResponse(['success' => true, 'message' => '設定を保存しました。']);
}

function adminStatus(PDO $pdo): void
{
    jsonResponse([
        'success' => true,
        'adminPasswordConfigured' => adminPasswordHash($pdo) !== '',
    ]);
}

function initializeAdminPassword(PDO $pdo): void
{
    if (adminPasswordHash($pdo) !== '') {
        jsonResponse(['success' => false, 'message' => '管理者パスワードは設定済みです。'], 409);
    }

    $newPassword = trim((string)($_POST['new_admin_password'] ?? ''));
    $confirmPassword = trim((string)($_POST['new_admin_password_confirm'] ?? $newPassword));
    if ($newPassword === '') {
        jsonResponse(['success' => false, 'message' => '管理者パスワードを入力してください。'], 400);
    }

    if ($newPassword !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => '確認用パスワードが一致しません。'], 400);
    }

    $settings = loadSettings($pdo);
    $settings['security']['adminPasswordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    if (isset($settings['config']) && is_array($settings['config']) && array_key_exists('bbsTitle', $settings['config'])) {
        $settings['config']['manualTitle'] = (string)$settings['config']['bbsTitle'];
    }
    saveSettings($pdo, $settings);
    jsonResponse(['success' => true, 'message' => '管理者パスワードを設定しました。']);
}

function changeAdminPassword(PDO $pdo): void
{
    requireAdmin();
    $newPassword = trim((string)($_POST['new_admin_password'] ?? ''));
    $confirmPassword = trim((string)($_POST['new_admin_password_confirm'] ?? $newPassword));
    if ($newPassword !== $confirmPassword) {
        jsonResponse(['success' => false, 'message' => '確認用パスワードが一致しません。'], 400);
    }
    if ($newPassword === '') {
        jsonResponse(['success' => false, 'message' => '新しい管理パスワードを入力してください。'], 400);
    }

    $settings = loadSettings($pdo);
    $settings['security']['adminPasswordHash'] = password_hash($newPassword, PASSWORD_DEFAULT);
    if (isset($settings['config']) && is_array($settings['config']) && array_key_exists('bbsTitle', $settings['config'])) {
        $settings['config']['manualTitle'] = (string)$settings['config']['bbsTitle'];
    }
    saveSettings($pdo, $settings);
    jsonResponse(['success' => true, 'message' => '管理パスワードを変更しました。']);
}

function defaultSettings(): array
{
    return [
        'config' => [
            'bbsTitle' => 'ThreadForge',
            'homePageUrl' => '/',
            'manualTitle' => 'ThreadForge',
            'manualBody' => defaultManualBody(),
            'tweetEnabled' => false,
            'tweetBaseUrl' => '',
            'tweetConsumerKey' => '',
            'tweetConsumerSecret' => '',
            'tweetAccessToken' => '',
            'tweetAccessTokenSecret' => '',
            'blueskyEnabled' => false,
            'blueskyServiceUrl' => '',
            'blueskyPublicApiUrl' => '',
            'blueskyHandle' => '',
            'blueskyAppPassword' => '',
            'mastodonEnabled' => false,
            'mastodonInstanceUrl' => '',
            'mastodonAccessToken' => '',
            'mastodonVisibility' => '',
            'misskeyEnabled' => false,
            'misskeyInstanceUrl' => '',
            'misskeyAccessToken' => '',
            'gdgdEnabled' => false,
            'gdgdLabel' => '特殊投稿',
            'eejanaikaOmigotoText' => 'お美事にございまする',
            'eejanaikaOmigotoColor' => '#ff72ff',
            'eejanaikaGoodjobText' => 'いい仕事してますねぇ',
            'eejanaikaGoodjobColor' => '#27a8ff',
            'eejanaikaEejanaikaText' => 'ええじゃないか',
            'eejanaikaEejanaikaColor' => '#fff200',
            'socialHashtags' => '#art',
            'ssoEnabled' => false,
            'ssoSharedSecret' => '',
            'listOrder' => 'number',
            'maxUploadBytes' => 5100000,
            'maxImageWidth' => 1280,
            'maxImageHeight' => 960,
            'allowedImageTypes' => ['gif', 'png', 'jpeg', 'jpg', 'bmp'],
        ],
        'skin' => [
            'normalFrameColor' => '#a23dff',
            'gdgdFrameColor' => '#6dffc0',
            'backgroundColor' => '#000000',
            'pageTextColor' => '#ffffff',
            'linkColor' => '#58a6ff',
            'panelBackgroundColor' => '#101821',
            'panelTitleBackgroundColor' => '#5b6572',
            'panelBorderColor' => '#738196',
            'labelColor' => '#8fc0ff',
            'inputBackgroundColor' => '#30343a',
            'inputTextColor' => '#ffffff',
            'buttonBackgroundColor' => '#3f74ff',
            'buttonTextColor' => '#ffffff',
            'buttonBorderColor' => '#8fb0ff',
            'secondaryButtonBackgroundColor' => '#2f333b',
            'secondaryButtonTextColor' => '#ffffff',
            'secondaryButtonBorderColor' => '#7a8495',
            'normalHeaderColor' => '#7f00a8',
            'normalTextColor' => '#ffffff',
            'gdgdHeaderColor' => '#39988a',
            'gdgdTextColor' => '#ffffff',
            'replyBorderColor' => '#7a8495',
            'quickReactionButtonBackgroundColor' => '#30343b',
            'dangerColor' => '#ff7c7c',
            'warningColor' => '#ffd36a',
            'successColor' => '#8dff8d',
        ],
        'security' => [
            'adminPasswordHash' => '',
            'cronApiKey' => '',
        ],
    ];
}

function defaultManualBody(): string
{
    return implode("\n", [
        'この取説は、このサイトを利用する方向けの案内です。',
        '',
        '# 【HOME】',
        '- サイト管理者が設定したHOMEリンクへ移動します。',
        '',
        '# 【一覧】',
        '## 投稿を見る',
        '- 投稿作品とコメントの一部を、サイトで設定された並び順で確認できます。',
        '- 作品画像、タイトル、本文、作者名、投稿日時、閲覧数、コメント数、簡単リアクション数、SNSリアクション数が表示されます。',
        '- タイトルや画像を選ぶと、その投稿の個別ページを開きます。',
        '- 作者アイコンにカーソルを合わせると拡大表示され、クリックするとその作者の作品一覧を表示できます。',
        '## コメントと簡単リアクション',
        '- 各投稿にはコメントできます。',
        '- 簡単リアクションでは、サイトで設定された定型文を短いコメントとして送信できます。',
        '',
        '# 【投稿】',
        '- 投稿では名前、タイトル、URL / HOME、画像、本文、投稿パスワードを入力します。',
        '- 画像は管理画面で許可された形式を使用できます。初期設定ではGIF、PNG、JPEG、JPG、BMPに対応し、選択した画像は投稿前にプレビューできます。',
        '- 特殊投稿が有効なサイトでは、通常投稿とは別の枠色で投稿できます。',
        '- SNS転記が有効なサイトでは、投稿時にSNSへ転記できます。SNS転記OFFを選ぶと転記しません。',
        '',
        '# 【削除】',
        '- 投稿やコメントは、投稿時のパスワードで削除できます。',
        '- 削除した投稿は消え、管理者しか復元できないため慎重に操作してください。',
        '- 親投稿を削除すると、その投稿についていたコメントも非表示になります。',
        '- 定型文の簡単リアクションを削除するには、管理者パスワードが必要です。',
        '',
        '# 【編集】',
        '- 投稿やコメントは、投稿時のパスワードで編集できます。',
        '- 投稿編集では、タイトル、本文、URL / HOME、画像を変更できます。',
        '- 画像を選ぶと、現在の画像プレビューが選択した画像に切り替わります。',
        '- SNS側に転記された内容には編集内容は反映されません。',
        '- 定型文の簡単リアクションは編集できません。',
        '',
        '# 【検索】',
        '- キーワードで投稿やコメントを検索できます。',
        '- 検索結果から一覧内の該当投稿位置へ移動できます。',
        '',
        '# 【順位】',
        '- コメント数、閲覧数、いいね数などの項目を選び、数が多い順に投稿を確認できます。',
        '- 順位から一覧内の該当投稿位置へ移動できます。',
        '',
        '# 【取説】',
        '- このサイトの使い方を確認できます。',
        '',
        '# 【ログイン】',
        '- ID、ログインパスワード、アイコンを登録してログインできます。',
        '- ログイン中は投稿や返信の名前欄が、名前とひとことを組み合わせた表示になります。',
        '- ログイン中の投稿や返信には、名前の前にアイコンが表示されます。',
        '- ログイン中は、設定した名前、投稿パスワード、URL / HOMEが投稿画面や返信画面の初期値になります。',
        '',
        '# 【ユーザー設定】',
        '- ユーザー設定では、名前、投稿パスワード、URL / HOME、アイコンを変更できます。',
        '- 自分の投稿や返信の一覧を確認できます。',
        '- 自分のIDで投稿したものは、一覧から表示、編集、削除できます。',
        '- 自分の作品として紐づけた投稿は作品一覧や統計に含まれます。',
        '- 自分の投稿作品について、閲覧数やリアクション数などの統計を確認できます。',
    ]);
}
function legacyDefaultManualBody(): string
{
    return '';
}

function previousDefaultManualBody(): string
{
    return '';
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
    if (($settings['config']['manualTitle'] ?? '') === 'ThreadForge 取扱説明書') {
        $settings['config']['manualTitle'] = 'ThreadForge';
    }
    if (($settings['config']['gdgdLabel'] ?? '') === 'gdgd投稿') {
        $settings['config']['gdgdLabel'] = '特殊投稿';
    }
    if (
        ($settings['skin']['normalHeaderColor'] ?? '') === '#39988a'
        && ($settings['skin']['gdgdHeaderColor'] ?? '') === '#7f00a8'
    ) {
        $settings['skin']['normalHeaderColor'] = '#7f00a8';
        $settings['skin']['gdgdHeaderColor'] = '#39988a';
    }
    $settings['config']['manualTitle'] = (string)($settings['config']['bbsTitle'] ?? 'ThreadForge');
    $defaultBodies = [legacyDefaultManualBody(), previousDefaultManualBody()];
    if (is_string($settings['config']['manualBody'] ?? null)) {
        $body = (string)$settings['config']['manualBody'];
        if (str_starts_with($body, 'ThreadForge は、スレッド形式で作品や記事を投稿できる掲示板です。')) {
            $defaultBodies[] = $body;
        }
    }
    if (in_array(($settings['config']['manualBody'] ?? ''), $defaultBodies, true)) {
        $settings['config']['manualBody'] = defaultManualBody();
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

function paginationParams(PDO $pdo): array
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $requestLimit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);
    $settings = loadSettings($pdo);
    $configuredLimit = filter_var($settings['config']['logView'] ?? null, FILTER_VALIDATE_INT);
    $page = max(1, $page);
    if ($requestLimit !== false && $requestLimit !== null) {
        $limit = min(1000, max(1, $requestLimit));
        return [$limit, ($page - 1) * $limit];
    }
    if ($configuredLimit === false || $configuredLimit === null || $configuredLimit <= 0) {
        return [null, 0];
    }
    $limit = min(1000, max(1, $configuredLimit));
    return [$limit, ($page - 1) * $limit];
}

function threadPeriodFilterFromRequest(): array
{
    $years = array_values(array_filter(array_unique(array_map('trim', explode(',', (string)($_GET['years'] ?? '')))), static fn(string $year): bool => preg_match('/^\d{4}$/', $year) === 1));
    $months = array_values(array_filter(array_unique(array_map('trim', explode(',', (string)($_GET['months'] ?? '')))), static fn(string $month): bool => preg_match('/^\d{4}-\d{2}$/', $month) === 1));
    $monthYears = array_unique(array_map(static fn(string $month): string => substr($month, 0, 4), $months));
    $wholeYears = array_values(array_diff($years, $monthYears));
    $conditions = [];
    $params = [];

    if ($wholeYears !== []) {
        $yearPlaceholders = [];
        foreach ($wholeYears as $index => $year) {
            $key = ':filter_year_' . $index;
            $yearPlaceholders[] = $key;
            $params[$key] = $year;
        }
        $conditions[] = 'substr(created_at, 1, 4) IN (' . implode(', ', $yearPlaceholders) . ')';
    }

    if ($months !== []) {
        $monthPlaceholders = [];
        foreach ($months as $index => $month) {
            $key = ':filter_month_' . $index;
            $monthPlaceholders[] = $key;
            $params[$key] = $month;
        }
        $conditions[] = 'substr(created_at, 1, 7) IN (' . implode(', ', $monthPlaceholders) . ')';
    }

    if ($conditions === []) {
        return ['', []];
    }

    return [' AND (' . implode(' OR ', $conditions) . ')', $params];
}

function listThreadOrder(array $settings): string
{
    $value = (string)($settings['config']['listOrder'] ?? 'number');
    return $value === 'createdAt' ? 'createdAt' : 'number';
}

function socialDebugLog(string $message, array $data = []): void
{
    $logFile = __DIR__ . '/mastodon_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== []) {
        $line .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}
