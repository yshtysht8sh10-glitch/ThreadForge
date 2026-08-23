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
        case 'uploaderSettings':
            uploaderSettings($pdo);
            break;
        case 'listUploaderFiles':
            listUploaderFiles($pdo);
            break;
        case 'uploadUploaderFile':
            uploadUploaderFile($pdo);
            break;
        case 'deleteUploaderFile':
            deleteUploaderFile($pdo);
            break;
        case 'listDeletedUploaderFiles':
            listDeletedUploaderFiles($pdo);
            break;
        case 'adminDeleteUploaderFiles':
            adminDeleteUploaderFiles($pdo);
            break;
        case 'restoreUploaderFiles':
            restoreUploaderFiles($pdo);
            break;
        case 'purgeUploaderFiles':
            purgeUploaderFiles($pdo);
            break;
        case 'materialsSettings':
            materialsSettings($pdo);
            break;
        case 'listMaterialItems':
            listMaterialItems($pdo);
            break;
        case 'getMaterialItem':
            getMaterialItem($pdo);
            break;
        case 'createMaterialItem':
            createMaterialItem($pdo);
            break;
        case 'updateMaterialItem':
            updateMaterialItem($pdo);
            break;
        case 'verifyMaterialPassword':
            verifyMaterialPassword($pdo);
            break;
        case 'deleteMaterialItem':
            deleteMaterialItem($pdo);
            break;
        case 'listDeletedMaterialItems':
            listDeletedMaterialItems($pdo);
            break;
        case 'adminDeleteMaterialItems':
            adminDeleteMaterialItems($pdo);
            break;
        case 'restoreMaterialItems':
            restoreMaterialItems($pdo);
            break;
        case 'purgeMaterialItems':
            purgeMaterialItems($pdo);
            break;
        case 'saveMaterialCatalog':
            saveMaterialCatalog($pdo);
            break;
        case 'assignMaterialAuthor':
            assignMaterialAuthor($pdo);
            break;
        case 'materialAnalytics':
            materialAnalytics($pdo);
            break;
        case 'recordMaterialView':
            recordMaterialView($pdo);
            break;
        case 'updateMaterialProfile':
            updateMaterialProfile($pdo);
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
        case 'listSocialLogs':
            listSocialLogs($pdo);
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
    $requestedPage = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
    $settings = loadSettings($pdo);
    $listOrder = listThreadOrder($settings);
    [$periodWhere, $periodParams] = threadPeriodFilterFromRequest();
    $targetId = filter_input(INPUT_GET, 'target_id', FILTER_VALIDATE_INT);
    $targetDirection = (string)($_GET['target_direction'] ?? 'older');
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
            $targetOffset = $limit === null ? 0 : intdiv((int)$beforeStmt->fetchColumn(), $limit) * $limit;
            if ($targetDirection === 'newer') {
                if ($limit !== null) {
                    $newerEnd = $targetOffset - (($requestedPage - 1) * $limit);
                    if ($newerEnd <= 0) {
                        jsonResponse([]);
                    }
                    $offset = max(0, $newerEnd - $limit);
                    $limit = $newerEnd - $offset;
                }
            } else {
                $offset = $limit === null ? 0 : $targetOffset + (($requestedPage - 1) * $limit);
            }
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
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    if (toBoolFlag($config['ssoEnabled'] ?? false)) {
        jsonResponse(['success' => false, 'message' => 'SSOログインが有効なため、ThreadForge側ではアカウントを新規作成できません。親サイト側でアカウントを作成してください。'], 403);
    }

    $loginId = normalizeLoginId($_POST['login_id'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? $password);
    $displayName = normalizeString($_POST['display_name'] ?? $loginId);
    $postPassword = normalizeString($_POST['post_password'] ?? '');
    $homeUrl = normalizeUrl($_POST['home_url'] ?? null);

    if ($loginId === '' || $password === '') {
        jsonResponse(['success' => false, 'message' => 'IDとログインパスワードを入力してください。'], 400);
    }
    if (!hash_equals($password, $passwordConfirm)) {
        jsonResponse(['success' => false, 'message' => 'ログインパスワードと確認入力が一致しません。'], 400);
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
        socialDebugLog('x post start', ['post_id' => $insertedId, 'text_length' => mb_strlen($tweetText, 'UTF-8'), 'has_image' => $imagePath !== null]);
        $tweetResult = publishTweetFromSettings($settings, $tweetText, $imagePath);
        socialDebugLogResult('x post result', $tweetResult);
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
            socialDebugLogResult($platform . ' post result', $result);
            if ($result['success']) {
                saveSocialPublishResult($pdo, $insertedId, $platform, $result);
                $responseMessage .= ' ' . socialPlatformLabel($platform) . 'へ投稿しました。';
            } else {
                $responseMessage .= ' ' . socialPlatformLabel($platform) . '投稿は失敗しました: ' . $result['message'];
            }
        }
    }

    jsonResponse([
        'success' => true,
        'message' => $responseMessage,
        'tweet_url' => $tweetUrl,
        'id' => $insertedId,
        'thread_id' => $threadId === 0 ? $insertedId : $threadId,
    ]);
}

function fillTweetPostId(string $tweetText, int $postId): string
{
    return preg_replace('/000000/', (string)$postId, $tweetText) ?? $tweetText;
}

function defaultBoardPostUrl(): ?string
{
    $baseUrl = publicBoardBaseUrl();
    if ($baseUrl === null) {
        return null;
    }

    return rtrim($baseUrl, '/') . '/?target=000000#post-000000';
}

function publicBoardBaseUrl(): ?string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (is_string($referer) && trim($referer) !== '') {
        $parts = parse_url($referer);
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $path = isset($parts['path']) ? (string)$parts['path'] : '/';
            $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
            if ($dir === '' || $dir === '.') {
                $dir = '';
            }
            return $parts['scheme'] . '://' . $parts['host'] . $port . $dir;
        }
    }

    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if (!is_string($host) || trim($host) === '') {
        return null;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    if ($scriptDir === '' || $scriptDir === '.') {
        $scriptDir = '';
    }
    return ($https ? 'https://' : 'http://') . $host . $scriptDir;
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
        socialDebugLog('bluesky post start', ['post_id' => $postId, 'text_length' => mb_strlen($text, 'UTF-8'), 'has_image' => $imagePath !== null]);
        $results['bluesky'] = publishBlueskyPost($config, $text, $imagePath);
    }
    if (toBoolFlag($config['mastodonEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('mastodon', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        $results['mastodon'] = publishMastodonPost($config, $text, $imagePath);
    }
    if (toBoolFlag($config['misskeyEnabled'] ?? false)) {
        $text = fillTweetPostId(buildSocialPostText('misskey', $name, $title, $message, $sourceUrl, (string)($config['socialHashtags'] ?? '#art')), $postId);
        socialDebugLog('misskey post start', ['post_id' => $postId, 'text_length' => mb_strlen($text, 'UTF-8'), 'has_image' => $imagePath !== null]);
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
                (SELECT COUNT(*) FROM material_items m WHERE m.user_id = u.id) AS material_count,
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
        'materials_author_name' => $row['materials_author_name'] ?? null,
        'materials_default_terms' => json_decode((string)($row['materials_default_terms'] ?? '{}'), true) ?: [],
        'created_at' => (string)$row['created_at'],
        'updated_at' => (string)$row['updated_at'],
        'post_count' => (int)$row['post_count'],
        'claim_count' => (int)$row['claim_count'],
        'material_count' => (int)$row['material_count'],
        'last_login_at' => $row['last_login_at'] ?? ($row['last_session_at'] ?? null),
        'last_session_at' => $row['last_session_at'] ?? null,
        'active_session_count' => (int)($row['active_session_count'] ?? 0),
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));

    jsonResponse(['success' => true, 'users' => $users]);
}

function adminUpdateUser(PDO $pdo): void
{
    requireAdmin();
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    if (toBoolFlag($config['ssoEnabled'] ?? false)) {
        jsonResponse(['success' => false, 'message' => 'SSOログインが有効なため、ThreadForge側ではユーザー情報を編集できません。親サイト側で編集してください。'], 403);
    }

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
    $materialsAuthorName = trim((string)($_POST['materials_author_name'] ?? ($user['materials_author_name'] ?? $displayName)));
    $materialsDefaultTerms = json_decode((string)($_POST['materials_default_terms'] ?? ($user['materials_default_terms'] ?? '{}')), true);
    $newPassword = trim((string)($_POST['login_password'] ?? ''));
    $newPasswordConfirm = trim((string)($_POST['login_password_confirm'] ?? $newPassword));

    if ($loginId === '' || $displayName === '' || $materialsAuthorName === '') {
        jsonResponse(['success' => false, 'message' => 'ID、名前、素材庫の作者名を入力してください。'], 400);
    }
    if (mb_strlen($displayName) > 30 || mb_strlen($materialsAuthorName) > 80) {
        jsonResponse(['success' => false, 'message' => '名前は30文字以内、素材庫の作者名は80文字以内で入力してください。'], 400);
    }
    if (!is_array($materialsDefaultTerms)) {
        jsonResponse(['success' => false, 'message' => '素材庫の利用規約初期値が不正です。'], 400);
    }
    if ($newPassword !== $newPasswordConfirm) {
        jsonResponse(['success' => false, 'message' => '新しいログインパスワードと確認入力が一致しません。'], 400);
    }
    if (strlen($postPassword) > 8) {
        $postPassword = substr($postPassword, 0, 8);
    }

    $duplicate = findUserByLoginId($pdo, $loginId);
    if ($duplicate && (int)$duplicate['id'] !== $id) {
        jsonResponse(['success' => false, 'message' => 'このIDは既に使われています。'], 409);
    }

    $iconPath = $user['icon_path'] ?? null;
    if (toBoolFlag($_POST['remove_icon'] ?? false)) {
        deleteStorageFile($iconPath);
        $iconPath = null;
    }
    if (isset($_FILES['icon']) && (int)($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $newIconPath = saveUploadedUserIcon($_FILES['icon'], $id);
        if ($iconPath && $newIconPath !== $iconPath) {
            deleteStorageFile($iconPath);
        }
        $iconPath = $newIconPath;
    }

    $fields = 'login_id = :login_id, display_name = :display_name, post_password = :post_password,
               home_url = :home_url, icon_path = :icon_path, materials_author_name = :materials_author_name,
               materials_default_terms = :materials_default_terms, updated_at = :updated_at';
    $params = [
        ':id' => $id,
        ':login_id' => $loginId,
        ':display_name' => $displayName,
        ':post_password' => $postPassword,
        ':home_url' => $homeUrl,
        ':icon_path' => $iconPath,
        ':materials_author_name' => $materialsAuthorName,
        ':materials_default_terms' => json_encode($materialsDefaultTerms, JSON_UNESCAPED_UNICODE),
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
    $settings = loadSettings($pdo);
    if (toBoolFlag($settings['config']['ssoEnabled'] ?? false)) {
        jsonResponse(['success' => false, 'message' => 'SSOログインが有効なため、ThreadForge側ではユーザー情報を消去できません。親サイト側で管理してください。'], 403);
    }
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
            $pdo->prepare('UPDATE material_items SET user_id = NULL WHERE user_id = :id')->execute([':id' => $id]);
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
                     materials_author_name = :materials_author_name,
                     materials_default_terms = "{}",
                     updated_at = :updated_at
                 WHERE id = :id'
            );
            $stmt->execute([
                ':id' => $id,
                ':login_id' => 'deleted_user_' . $id,
                ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ':display_name' => '削除済みユーザー',
                ':materials_author_name' => '削除済みユーザー',
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
        $pdo->prepare('UPDATE material_items SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
        $pdo->prepare('UPDATE user_sessions SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
        $pdo->prepare('UPDATE user_post_claims SET user_id = :new WHERE user_id = :old')->execute([':new' => $temporary, ':old' => $oldId]);
    }
    foreach ($map as $oldId => $newId) {
        $temporary = -abs((int)$oldId);
        $pdo->prepare('UPDATE users SET id = :new WHERE id = :old')->execute([':new' => $newId, ':old' => $temporary]);
        $pdo->prepare('UPDATE posts SET user_id = :new WHERE user_id = :old')->execute([':new' => $newId, ':old' => $temporary]);
        $pdo->prepare('UPDATE material_items SET user_id = :new WHERE user_id = :old')->execute([':new' => $newId, ':old' => $temporary]);
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

function listSocialLogs(PDO $pdo): void
{
    requireAdmin();
    $offset = max(0, filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0);
    $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 100;
    $limit = max(1, min(200, $limit));
    $logFile = __DIR__ . '/social_debug.log';
    $fallbackFile = __DIR__ . '/mastodon_debug.log';
    $file = is_file($logFile) ? $logFile : $fallbackFile;
    if (!is_file($file)) {
        jsonResponse(['success' => true, 'lines' => [], 'next_offset' => null, 'has_more' => false]);
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    $lines = array_reverse($lines);
    $slice = array_slice($lines, $offset, $limit);
    $items = array_map(static function (string $line): array {
        $isError = preg_match('/\[(ERROR|WARNING)\]/', $line) === 1;
        if (!$isError && preg_match('/\[(INFO)\]/', $line) !== 1) {
            $isError = preg_match('/failed|HTTP\s+[45]\d\d|success":false|error":\s*"[^"]+"/i', $line) === 1;
        }
        return [
            'text' => $line,
            'is_error' => $isError,
        ];
    }, $slice);
    $nextOffset = $offset + count($slice);
    jsonResponse([
        'success' => true,
        'lines' => $items,
        'next_offset' => $nextOffset < count($lines) ? $nextOffset : null,
        'has_more' => $nextOffset < count($lines),
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
    socialDebugLog('social reaction refresh start');
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
                socialDebugLog('social reaction fetch success', ['post_id' => (int)$row['id'], 'platform' => $platform, 'result' => $result]);
            } else {
                $errors[] = '#' . $row['id'] . ' ' . socialPlatformLabel($platform) . ': ' . $result['message'];
                socialDebugLog('social reaction fetch failed', ['post_id' => (int)$row['id'], 'platform' => $platform, 'message' => $result['message'] ?? ''], 'error');
            }
        }
    }
    socialDebugLog('social reaction refresh complete', ['checked_posts' => count($rows), 'updated' => $updated, 'errors' => count($errors)]);

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
    @set_time_limit(0);
    ignore_user_abort(true);
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
        'frontend_id' => FRONTEND_ID,
        'exported_at' => currentTimestamp(),
    ];
    $manifestJson = json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($manifestJson) || !$zip->addFromString('backup.json', $manifestJson)) {
        $zip->close();
        @unlink($zipPath);
        jsonResponse(['success' => false, 'message' => 'バックアップ情報をZIPへ追加できませんでした。'], 500);
    }
    if (is_file(DB_FILE)) {
        if (!$zip->addFile(DB_FILE, 'database.sqlite')) {
            $zip->close();
            @unlink($zipPath);
            jsonResponse(['success' => false, 'message' => 'データベースをZIPへ追加できませんでした。'], 500);
        }
        $zip->setCompressionName('database.sqlite', ZipArchive::CM_STORE);
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
    if (!$zip->close() || !is_file($zipPath)) {
        @unlink($zipPath);
        jsonResponse(['success' => false, 'message' => 'バックアップZIPを完成できませんでした。'], 500);
    }
    $zipSize = filesize($zipPath);
    if ($zipSize === false || $zipSize <= 0) {
        @unlink($zipPath);
        jsonResponse(['success' => false, 'message' => '作成したバックアップZIPが空です。'], 500);
    }

    $input = fopen($zipPath, 'rb');
    if ($input === false) {
        @unlink($zipPath);
        jsonResponse(['success' => false, 'message' => 'バックアップZIPを読み込めませんでした。'], 500);
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . FRONTEND_ID . '-full-backup-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . $zipSize);
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    while (!feof($input)) {
        $chunk = fread($input, 1024 * 1024);
        if ($chunk === false) {
            fclose($input);
            @unlink($zipPath);
            exit;
        }
        echo $chunk;
        flush();
    }
    fclose($input);
    @unlink($zipPath);
    exit;
}

function addReferencedBackupImages(PDO $pdo, ZipArchive $zip, array &$addedImages): void
{
    $queries = [
        'SELECT image_path AS path FROM posts WHERE image_path IS NOT NULL AND image_path <> ""',
        'SELECT icon_path AS path FROM users WHERE icon_path IS NOT NULL AND icon_path <> ""',
        'SELECT archive_path AS path FROM material_items WHERE archive_path IS NOT NULL AND archive_path <> ""',
        'SELECT image_path AS path FROM material_items WHERE image_path IS NOT NULL AND image_path <> ""',
        'SELECT path FROM material_media WHERE path IS NOT NULL AND path <> ""',
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
    $entryName = 'images/' . $basename;
    if (!$zip->addFile($path, $entryName)) {
        throw new RuntimeException('素材ファイルをバックアップZIPへ追加できませんでした: ' . $basename);
    }
    // Archives and media are already compressed. Storing them avoids minutes of redundant recompression.
    $zip->setCompressionName($entryName, ZipArchive::CM_STORE);
    $addedImages[$basename] = true;
}

function importBackup(PDO $pdo): void
{
    requireAdmin();
    if (empty($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
        $error = (int)($_FILES['backup']['error'] ?? UPLOAD_ERR_NO_FILE);
        $message = in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'バックアップZIPがサーバーのアップロード上限を超えています。PHPの upload_max_filesize と post_max_size を確認してください。'
            : 'バックアップファイルを選択してください。';
        jsonResponse(['success' => false, 'message' => $message], 400);
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

    $manifestJson = $zip->getFromName('backup.json');
    $manifest = is_string($manifestJson) ? json_decode($manifestJson, true) : null;
    $backupFrontendId = is_array($manifest) ? (string)($manifest['frontend_id'] ?? '') : '';
    if ($backupFrontendId !== '' && $backupFrontendId !== FRONTEND_ID) {
        $zip->close();
        jsonResponse(['success' => false, 'message' => '別フロント用のバックアップはインポートできません。'], 400);
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
        $requiredTable = match (FRONTEND_ID) {
            'file-uploader' => 'uploader_files',
            'materials-library' => 'material_items',
            'proxy-release' => 'material_items',
            'document-holder' => 'material_items',
            default => 'posts',
        };
        $test->query('SELECT COUNT(*) FROM ' . $requiredTable)->fetchColumn();
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
        ['table' => 'material_items', 'column' => 'archive_path'],
        ['table' => 'material_items', 'column' => 'image_path'],
        ['table' => 'material_media', 'column' => 'path'],
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
    $settings = loadSettings($pdo);
    $webMugenToken = trim((string)($settings['security']['webMugenApiToken'] ?? ''));
    unset($settings['security']['webMugenApiToken']);
    jsonResponse([
        'success' => true,
        'settings' => $settings,
        'webMugen' => [
            'tokenConfigured' => $webMugenToken !== '' || trim((string)(getenv('THREADFORGE_WEBMUGEN_CATALOG_SECRET') ?: '')) !== '',
        ],
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
    $skin = $settings['skin'] ?? [];

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
                'ssoEnabled' => toBoolFlag($config['ssoEnabled'] ?? false),
                'listOrder' => listThreadOrder($settings),
            ],
            'skin' => imageBoardPublicSkin($skin),
        ],
    ]);
}

function imageBoardPublicSkin(array $skin): array
{
    $defaults = imageBoardDefaultSkin();
    $result = [];
    foreach ($defaults as $key => $default) {
        $result[$key] = (string)($skin[$key] ?? $default);
    }
    return $result;
}

function uploaderSettings(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $skin = $settings['skin'] ?? [];
    $title = (string)($config['uploaderTitle'] ?? 'ファイルアップローダー');
    if ($title === 'ドット絵板新アップローダー') {
        $title = 'ファイルアップローダー';
    }

    jsonResponse([
        'success' => true,
        'settings' => [
            'title' => $title,
            'homePageUrl' => (string)($config['uploaderHomePageUrl'] ?? '../'),
            'allowedExtensions' => implode(' ', uploaderAllowedExtensions($config)),
            'maxUploadKb' => uploaderMaxUploadKb($config),
            'design' => uploaderDesignSettings($skin),
        ],
    ]);
}

function listUploaderFiles(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT id, stored_name, original_name, comment, size_bytes, created_at
         FROM uploader_files
         WHERE deleted_at IS NULL
         ORDER BY id DESC'
    );
    $rows = array_map(static function (array $row): array {
        return [
            'id' => (int)$row['id'],
            'filename' => (string)$row['stored_name'],
            'originalName' => (string)$row['original_name'],
            'comment' => (string)$row['comment'],
            'sizeBytes' => (int)$row['size_bytes'],
            'createdAt' => (string)$row['created_at'],
            'downloadUrl' => publicStoragePath((string)$row['stored_name']),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    jsonResponse(['success' => true, 'files' => $rows]);
}

function uploadUploaderFile(PDO $pdo): void
{
    if (FRONTEND_ID !== 'file-uploader' && !isPackagedSingleFrontendApp()) {
        jsonResponse(['success' => false, 'message' => 'このAPIはfile-uploader専用です。'], 403);
    }

    $file = $_FILES['file'] ?? null;
    if (!is_array($file) || !isset($file['tmp_name'], $file['name'], $file['size'])) {
        jsonResponse(['success' => false, 'message' => 'アップロードするファイルを選択してください。'], 400);
    }
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_OK);
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        jsonResponse([
            'success' => false,
            'message' => 'PHPのアップロード上限を超えています。upload_max_filesize: ' . ini_get('upload_max_filesize'),
        ], 400);
    }
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
        jsonResponse(['success' => false, 'message' => 'ファイルの受信に失敗しました。'], 400);
    }

    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $originalName = basename(str_replace('\\', '/', (string)$file['name']));
    $sizeBytes = (int)$file['size'];
    $validationError = uploaderFileValidationError($originalName, $sizeBytes, $config);
    if ($validationError !== null) {
        jsonResponse(['success' => false, 'message' => $validationError], 400);
    }

    $comment = trim((string)($_POST['comment'] ?? ''));
    $deleteKey = trim((string)($_POST['delete_key'] ?? ''));
    if ($deleteKey === '') {
        jsonResponse(['success' => false, 'message' => 'Delkeyを入力してください。'], 400);
    }

    $createdAt = currentTimestamp();
    $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO uploader_files
             (stored_name, original_name, comment, size_bytes, delete_key_hash, created_at)
             VALUES ("pending", :original_name, :comment, :size_bytes, :delete_key_hash, :created_at)'
        );
        $stmt->execute([
            ':original_name' => $originalName,
            ':comment' => $comment,
            ':size_bytes' => $sizeBytes,
            ':delete_key_hash' => password_hash($deleteKey, PASSWORD_DEFAULT),
            ':created_at' => $createdAt,
        ]);
        $id = (int)$pdo->lastInsertId();
        $storedName = 'file' . $id . '.' . $extension;
        $destination = STORAGE_DIR . DIRECTORY_SEPARATOR . $storedName;
        if (!is_dir(STORAGE_DIR)) {
            mkdir(STORAGE_DIR, 0775, true);
        }
        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            throw new RuntimeException('ファイルを保存できませんでした。');
        }
        $pdo->prepare('UPDATE uploader_files SET stored_name = :stored_name WHERE id = :id')
            ->execute([':stored_name' => $storedName, ':id' => $id]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    jsonResponse(['success' => true, 'message' => 'アップロードしました。']);
}

function deleteUploaderFile(PDO $pdo): void
{
    if (FRONTEND_ID !== 'file-uploader' && !isPackagedSingleFrontendApp()) {
        jsonResponse(['success' => false, 'message' => 'このAPIはfile-uploader専用です。'], 403);
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $deleteKey = trim((string)($_POST['delete_key'] ?? ''));
    if ($id === false || $id < 1 || $deleteKey === '') {
        jsonResponse(['success' => false, 'message' => '削除対象とDelkeyを入力してください。'], 400);
    }

    $stmt = $pdo->prepare(
        'SELECT id, delete_key_hash
         FROM uploader_files
         WHERE id = :id AND deleted_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) {
        jsonResponse(['success' => false, 'message' => '削除対象のファイルが見つかりません。'], 404);
    }
    if (!password_verify($deleteKey, (string)$file['delete_key_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Delkeyが違います。'], 403);
    }

    $update = $pdo->prepare(
        'UPDATE uploader_files
         SET deleted_at = :deleted_at
         WHERE id = :id AND deleted_at IS NULL'
    );
    $update->execute([
        ':id' => $id,
        ':deleted_at' => currentTimestamp(),
    ]);

    jsonResponse(['success' => true, 'message' => 'ファイルを削除しました。']);
}

function listDeletedUploaderFiles(PDO $pdo): void
{
    requireAdmin();
    $stmt = $pdo->query(
        'SELECT id, stored_name, original_name, comment, size_bytes, created_at, deleted_at
         FROM uploader_files
         WHERE deleted_at IS NOT NULL
         ORDER BY deleted_at DESC, id DESC'
    );
    jsonResponse([
        'success' => true,
        'files' => array_map('uploaderFileResponse', $stmt->fetchAll(PDO::FETCH_ASSOC)),
    ]);
}

function adminDeleteUploaderFiles(PDO $pdo): void
{
    requireAdmin();
    $ids = uploaderRequestIds();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        'UPDATE uploader_files
         SET deleted_at = ?
         WHERE deleted_at IS NULL AND id IN (' . $placeholders . ')'
    );
    $stmt->execute(array_merge([currentTimestamp()], $ids));
    jsonResponse(['success' => true, 'message' => $stmt->rowCount() . '件を削除しました。']);
}

function restoreUploaderFiles(PDO $pdo): void
{
    requireAdmin();
    $ids = uploaderRequestIds();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        'UPDATE uploader_files
         SET deleted_at = NULL
         WHERE deleted_at IS NOT NULL AND id IN (' . $placeholders . ')'
    );
    $stmt->execute($ids);
    jsonResponse(['success' => true, 'message' => $stmt->rowCount() . '件を復原しました。']);
}

function purgeUploaderFiles(PDO $pdo): void
{
    requireAdmin();
    $ids = uploaderRequestIds();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        'SELECT id, stored_name
         FROM uploader_files
         WHERE deleted_at IS NOT NULL AND id IN (' . $placeholders . ')'
    );
    $stmt->execute($ids);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($files === []) {
        jsonResponse(['success' => true, 'message' => '完全削除するファイルはありません。']);
    }

    $deleteIds = array_map(static fn(array $file): int => (int)$file['id'], $files);
    $deletePlaceholders = implode(',', array_fill(0, count($deleteIds), '?'));
    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare('DELETE FROM uploader_files WHERE id IN (' . $deletePlaceholders . ')');
        $delete->execute($deleteIds);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    foreach ($files as $file) {
        deleteStorageFile((string)$file['stored_name']);
    }
    jsonResponse(['success' => true, 'message' => count($files) . '件を完全削除しました。']);
}

function uploaderRequestIds(): array
{
    $raw = $_POST['ids'] ?? '';
    $parts = is_array($raw) ? $raw : explode(',', (string)$raw);
    $ids = array_values(array_unique(array_filter(
        array_map(static fn(mixed $id): int => (int)$id, $parts),
        static fn(int $id): bool => $id > 0
    )));
    if ($ids === []) {
        jsonResponse(['success' => false, 'message' => '対象ファイルを選択してください。'], 400);
    }
    return $ids;
}

function uploaderFileResponse(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'filename' => (string)$row['stored_name'],
        'originalName' => (string)$row['original_name'],
        'comment' => (string)$row['comment'],
        'sizeBytes' => (int)$row['size_bytes'],
        'createdAt' => (string)$row['created_at'],
        'downloadUrl' => publicStoragePath((string)$row['stored_name']),
    ];
}

function uploaderAllowedExtensions(array $config): array
{
    $raw = $config['uploaderAllowedExtensions'] ?? 'gif bmp png jpg jpeg zip txt avi swf';
    if (is_array($raw)) {
        $parts = $raw;
    } else {
        $parts = preg_split('/[\s,;]+/', strtolower((string)$raw)) ?: [];
    }
    $allowed = [];
    foreach ($parts as $part) {
        $extension = ltrim(strtolower(trim((string)$part)), '.');
        if ($extension !== '' && preg_match('/^[a-z0-9]+$/', $extension)) {
            $allowed[] = $extension;
        }
    }
    $allowed = array_values(array_unique($allowed));
    return $allowed === [] ? ['gif', 'bmp', 'png', 'jpg', 'jpeg', 'zip', 'txt', 'avi', 'swf'] : $allowed;
}

function uploaderMaxUploadKb(array $config): int
{
    $value = filter_var($config['uploaderMaxUploadKb'] ?? 20000, FILTER_VALIDATE_INT);
    return $value === false ? 20000 : max(1, min(2097152, (int)$value));
}

function uploaderFileValidationError(string $filename, int $sizeBytes, array $config): ?string
{
    $allowed = uploaderAllowedExtensions($config);
    $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowed, true)) {
        return '許可されていない拡張子です。許可: ' . implode(' ', $allowed);
    }
    if ($sizeBytes > uploaderMaxUploadKb($config) * 1024) {
        return 'ファイルサイズが上限を超えています。上限: ' . uploaderMaxUploadKb($config) . 'KB';
    }
    return null;
}

function uploaderDesignSettings(array $skin): array
{
    $defaults = uploaderDefaultDesign();
    $result = [];
    foreach ($defaults as $key => $default) {
        $result[$key] = (string)($skin['uploader' . ucfirst($key)] ?? $default);
    }
    return $result;
}

function uploaderDefaultDesign(): array
{
    return [
        'pageBackgroundColor' => '#0b0d10',
        'pageTextColor' => '#eef1f4',
        'linkColor' => '#8fb7e8',
        'shellBackgroundColor' => '#12171d',
        'contentBackgroundColor' => '#151b22',
        'formBackgroundColor' => '#1c232c',
        'titleStartColor' => '#626b76',
        'titleEndColor' => '#4f5863',
        'tableHeaderColor' => '#59636f',
        'borderColor' => '#707b88',
        'buttonBackgroundColor' => '#3974ee',
        'buttonTextColor' => '#ffffff',
        'activeTabColor' => '#315f9e',
        'errorColor' => '#ff8585',
    ];
}

function updateSettings(PDO $pdo): void
{
    requireAdmin();
    $currentSettings = loadSettings($pdo);
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
    $settings['security'] = is_array($settings['security'] ?? null) ? $settings['security'] : [];
    $settings['security']['webMugenApiToken'] = (string)($currentSettings['security']['webMugenApiToken'] ?? '');
    if (FRONTEND_ID === 'proxy-release' && array_key_exists('webmugen_api_token', $_POST)) {
        $token = trim((string)$_POST['webmugen_api_token']);
        if ($token !== '' && (strlen($token) < 16 || strlen($token) > 512 || preg_match('/[\x00-\x20\x7f]/', $token))) {
            jsonResponse(['success' => false, 'message' => 'WebMUGEN API Tokenは空白を含まない16〜512文字で指定してください。'], 400);
        }
        if ($token !== '') $settings['security']['webMugenApiToken'] = $token;
    }
    if (FRONTEND_ID === 'proxy-release') {
        $config = is_array($settings['config'] ?? null) ? $settings['config'] : [];
        $stageId = trim((string)($config['webMugenStageId'] ?? 'cyber'));
        if (preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/i', $stageId) !== 1) {
            jsonResponse(['success' => false, 'message' => 'WebMUGEN Stage IDが正しくありません。'], 400);
        }
        $settings['config']['webMugenStageId'] = $stageId;
        try {
            $settings['config']['webMugenApiUrl'] = webMugenCatalogEndpointFromValue((string)($config['webMugenApiUrl'] ?? ''), $_SERVER);
        } catch (InvalidArgumentException $error) {
            jsonResponse(['success' => false, 'message' => $error->getMessage()], 400);
        }
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


function materialDefaultTitle(): string
{
    return match (FRONTEND_ID) {
        'proxy-release' => 'Proxy Release',
        'document-holder' => 'DollMu,File',
        default => '■素材庫■',
    };
}

function materialDefaultDescription(): string
{
    return match (FRONTEND_ID) {
        'proxy-release' => 'MUGEN character release archives grouped by author and category.',
        'document-holder' => 'MUGENやドット絵制作に関する投稿ページを、タグと作者名で整理して保管します。',
        default => '制作に役立つ素材を、用途と作者ごとに整理して保管します。',
    };
}

function materialDefaultDescriptionBannerEnabled(): bool
{
    return FRONTEND_ID === 'document-holder';
}

function materialDefaultDescriptionBannerImageUrl(): string
{
    return FRONTEND_ID === 'document-holder' ? './assets/dollmu-file-banner.gif' : '';
}

function materialDefaultDescriptionBannerAlt(): string
{
    return FRONTEND_ID === 'document-holder' ? 'DollMu,File' : materialDefaultTitle();
}

function materialDefaultDescriptionBanners(): array
{
    if (FRONTEND_ID !== 'document-holder') {
        return [];
    }
    return array_map(
        static fn(string $file): array => ['imageUrl' => './assets/' . $file, 'linkUrl' => '', 'alt' => 'DollMu,File'],
        ['title01.gif', 'title02.gif', 'title03.gif', 'title04.gif']
    );
}

function materialDescriptionBanners(array $config): array
{
    $raw = $config['materialsDescriptionBanners'] ?? null;
    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
    if (is_array($decoded) && $decoded !== []) {
        return array_values(array_filter(array_map(static function ($banner): ?array {
            if (!is_array($banner)) {
                return null;
            }
            $imageUrl = trim((string)($banner['imageUrl'] ?? $banner['image_url'] ?? ''));
            if ($imageUrl === '') {
                return null;
            }
            return [
                'imageUrl' => $imageUrl,
                'linkUrl' => trim((string)($banner['linkUrl'] ?? $banner['link_url'] ?? '')),
                'alt' => trim((string)($banner['alt'] ?? materialDefaultDescriptionBannerAlt())),
            ];
        }, $decoded)));
    }

    if (!array_key_exists('materialsDescriptionBannerImageUrl', $config)) {
        return materialDefaultDescriptionBanners();
    }
    $single = trim((string)$config['materialsDescriptionBannerImageUrl']);
    if ($single !== '') {
        return [[
            'imageUrl' => $single,
            'linkUrl' => trim((string)($config['materialsDescriptionBannerLinkUrl'] ?? '')),
            'alt' => trim((string)($config['materialsDescriptionBannerAlt'] ?? materialDefaultDescriptionBannerAlt())),
        ]];
    }
    return materialDefaultDescriptionBanners();
}

function materialDefaultArchiveExtensions(): string
{
    return match (FRONTEND_ID) {
        'proxy-release' => 'zip',
        'document-holder' => 'zip',
        default => 'zip 7z rar',
    };
}

function materialsSettings(PDO $pdo): void
{
    $settings = loadSettings($pdo);
    $config = $settings['config'] ?? [];
    $skin = $settings['skin'] ?? [];
    $tags = $pdo->query('SELECT id, name, parent_id, sort_order FROM material_tags ORDER BY COALESCE(parent_id, id), parent_id IS NOT NULL, sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
    $terms = $pdo->query('SELECT id, label, description, sort_order FROM material_terms ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse([
        'success' => true,
        'settings' => [
            'title' => (string)($config['materialsTitle'] ?? materialDefaultTitle()),
            'description' => (string)($config['materialsDescription'] ?? materialDefaultDescription()),
            'descriptionBannerEnabled' => toBoolFlag($config['materialsDescriptionBannerEnabled'] ?? materialDefaultDescriptionBannerEnabled()),
            'descriptionBannerImageUrl' => (string)($config['materialsDescriptionBannerImageUrl'] ?? materialDefaultDescriptionBannerImageUrl()),
            'descriptionBannerLinkUrl' => (string)($config['materialsDescriptionBannerLinkUrl'] ?? ''),
            'descriptionBannerAlt' => (string)($config['materialsDescriptionBannerAlt'] ?? materialDefaultDescriptionBannerAlt()),
            'descriptionBannerIntervalMs' => max(1000, (int)($config['materialsDescriptionBannerIntervalMs'] ?? 5000)),
            'descriptionBanners' => materialDescriptionBanners($config),
            'homePageUrl' => (string)($config['materialsHomePageUrl'] ?? '../'),
            'trialPlayUrl' => (string)($config['proxyTrialPlayUrl'] ?? ''),
            'manualBody' => (string)($config['materialsManualBody'] ?? materialsDefaultManualBody()),
            'groupParent' => in_array(($config['materialsGroupParent'] ?? 'tag'), ['tag', 'author'], true) ? (string)$config['materialsGroupParent'] : 'tag',
            'maxArchiveKb' => max(1, (int)($config['materialsMaxArchiveKb'] ?? 102400)),
            'maxImageKb' => max(1, (int)($config['materialsMaxImageKb'] ?? 10240)),
            'allowedArchiveExtensions' => (string)($config['materialsAllowedArchiveExtensions'] ?? materialDefaultArchiveExtensions()),
            'ssoEnabled' => toBoolFlag($config['ssoEnabled'] ?? false),
            'design' => [
                'pageBackgroundColor' => (string)($skin['materialsPageBackgroundColor'] ?? '#050505'),
                'pageTextColor' => (string)($skin['materialsPageTextColor'] ?? '#f2f2f2'),
                'headerBackgroundColor' => (string)($skin['materialsHeaderBackgroundColor'] ?? '#000000'),
                'headerTextColor' => (string)($skin['materialsHeaderTextColor'] ?? '#ffffff'),
                'headerBorderColor' => (string)($skin['materialsHeaderBorderColor'] ?? '#222222'),
                'panelBackgroundColor' => (string)($skin['materialsPanelBackgroundColor'] ?? '#111820'),
                'panelBorderColor' => (string)($skin['materialsPanelBorderColor'] ?? '#6c7787'),
                'headingBackgroundColor' => (string)($skin['materialsHeadingBackgroundColor'] ?? '#59636f'),
                'headingBackgroundImageUrl' => (string)($skin['materialsHeadingBackgroundImageUrl'] ?? ''),
                'headingTextColor' => (string)($skin['materialsHeadingTextColor'] ?? '#ffffff'),
                'accentColor' => (string)($skin['materialsAccentColor'] ?? '#8fb0ff'),
                'buttonBackgroundColor' => (string)($skin['materialsButtonBackgroundColor'] ?? '#3974ee'),
                'buttonTextColor' => (string)($skin['materialsButtonTextColor'] ?? '#ffffff'),
                'secondaryButtonBackgroundColor' => (string)($skin['materialsSecondaryButtonBackgroundColor'] ?? '#303640'),
                'secondaryButtonTextColor' => (string)($skin['materialsSecondaryButtonTextColor'] ?? '#ffffff'),
                'dangerButtonBackgroundColor' => (string)($skin['materialsDangerButtonBackgroundColor'] ?? '#c44141'),
                'dangerButtonTextColor' => (string)($skin['materialsDangerButtonTextColor'] ?? '#ffffff'),
                'inputBackgroundColor' => (string)($skin['materialsInputBackgroundColor'] ?? '#282f38'),
                'inputTextColor' => (string)($skin['materialsInputTextColor'] ?? '#ffffff'),
                'formLabelColor' => (string)($skin['materialsFormLabelColor'] ?? '#aac4df'),
                'editorBackgroundColor' => (string)($skin['materialsEditorBackgroundColor'] ?? '#070b10'),
                'editorTextColor' => (string)($skin['materialsEditorTextColor'] ?? '#f5f7fb'),
                'editorBorderColor' => (string)($skin['materialsEditorBorderColor'] ?? '#6c7787'),
                'toolbarBackgroundColor' => (string)($skin['materialsToolbarBackgroundColor'] ?? '#0c1219'),
                'selectionBackgroundColor' => (string)($skin['materialsSelectionBackgroundColor'] ?? '#090e14'),
                'selectionHoverBackgroundColor' => (string)($skin['materialsSelectionHoverBackgroundColor'] ?? '#151f2a'),
                'selectionTextColor' => (string)($skin['materialsSelectionTextColor'] ?? '#f2f2f2'),
                'selectionMetaColor' => (string)($skin['materialsSelectionMetaColor'] ?? '#aab4c0'),
                'imageBackgroundColor' => (string)($skin['materialsImageBackgroundColor'] ?? '#020202'),
                'mutedTextColor' => (string)($skin['materialsMutedTextColor'] ?? '#aab4c0'),
                'listRowTextColor' => (string)($skin['materialsListRowTextColor'] ?? '#f2f2f2'),
                'listRowMetaColor' => (string)($skin['materialsListRowMetaColor'] ?? '#aab4c0'),
                'listRowBorderColor' => (string)($skin['materialsListRowBorderColor'] ?? '#3e4753'),
                'tocGroupTitleColor' => (string)($skin['materialsTocGroupTitleColor'] ?? '#f2f2f2'),
                'positiveColor' => (string)($skin['materialsPositiveColor'] ?? '#72e9a2'),
                'negativeColor' => (string)($skin['materialsNegativeColor'] ?? '#ff9292'),
                'unknownColor' => (string)($skin['materialsUnknownColor'] ?? '#f0cf78'),
            ],
        ],
        'tags' => array_map(static fn(array $row): array => [
            'id' => (int)$row['id'], 'name' => (string)$row['name'], 'parentId' => $row['parent_id'] === null ? null : (int)$row['parent_id'], 'sortOrder' => (int)$row['sort_order'],
        ], $tags),
        'terms' => array_map(static fn(array $row): array => [
            'id' => (int)$row['id'], 'label' => (string)$row['label'],
            'description' => (string)$row['description'], 'sortOrder' => (int)$row['sort_order'],
        ], $terms),
    ]);
}

function materialsDefaultManualBody(): string
{
    if (FRONTEND_ID === 'document-holder') {
        return "ドキュメントホルダーでは、HTMLフォルダや画面上で書いた文章を投稿できます。\n\n"
            . "投稿時は index.html / index.htm を含むフォルダ、単体HTML、または画面上で作成した文章を登録できます。\n"
            . "投稿されたドキュメントはタグと作者名で整理されます。\n"
            . "編集・削除には投稿パスワード、または投稿したユーザーでのログインが必要です。";
    }
    return "素材庫では、圧縮ファイルを主役として配布素材を保管します。\n\n"
        . "投稿時は素材ファイル、説明画像、名称、作者名、タグ、利用規約への回答を登録してください。\n"
        . "ログイン投稿はユーザーID単位、ゲスト投稿は作者名単位で一覧に整理されます。\n"
        . "編集・削除には投稿パスワード、または投稿したユーザーでのログインが必要です。";
}

function listMaterialItems(PDO $pdo): void
{
    $stmt = $pdo->query(
        'SELECT m.*, t.name AS tag_name, t.parent_id AS tag_parent_id, COALESCE(pt.id, t.id) AS primary_tag_id, COALESCE(pt.name, t.name) AS primary_tag_name, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.id END AS subtag_id, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.name END AS subtag_name, u.icon_path AS user_icon_path, u.login_id AS user_login_id
         FROM material_items m JOIN material_tags t ON t.id = m.tag_id
         LEFT JOIN material_tags pt ON pt.id = t.parent_id
         LEFT JOIN users u ON u.id = m.user_id
         WHERE m.deleted_at IS NULL
         ORDER BY t.sort_order, t.id, lower(m.author_name), m.name, m.id'
    );
    jsonResponse(['success' => true, 'items' => array_map(
        fn(array $row): array => buildMaterialItem($pdo, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    )]);
}

function getMaterialItem(PDO $pdo): void
{
    $id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $item = $id ? findMaterialItem($pdo, (int)$id, false) : null;
    if (!$item) {
        jsonResponse(['success' => false, 'message' => '素材が見つかりません。'], 404);
    }
    jsonResponse(['success' => true, 'item' => buildMaterialItem($pdo, $item)]);
}

function buildMaterialItem(PDO $pdo, array $row): array
{
    $termStmt = $pdo->prepare(
        'SELECT t.id, t.label, t.description, mit.accepted
         FROM material_terms t
         LEFT JOIN material_item_terms mit ON mit.term_id = t.id AND mit.item_id = :item_id
         ORDER BY t.sort_order, t.id'
    );
    $termStmt->execute([':item_id' => (int)$row['id']]);
    $mediaStmt = $pdo->prepare(
        'SELECT id, path, original_name, size_bytes FROM material_media
         WHERE item_id = :item_id ORDER BY sort_order, id'
    );
    $mediaStmt->execute([':item_id' => (int)$row['id']]);
    $userId = isset($row['user_id']) ? (int)$row['user_id'] : null;
    return [
        'id' => (int)$row['id'],
        'userId' => $userId,
        'authorKey' => $userId ? 'user:' . $userId : 'guest:' . (string)$row['author_name'],
        'authorName' => (string)$row['author_name'],
        'authorIcon' => publicStoragePath($row['user_icon_path'] ?? null),
        'name' => (string)$row['name'],
        'notes' => (string)$row['notes'],
        'tagId' => (int)$row['tag_id'],
        'tagName' => (string)$row['tag_name'],
        'primaryTagId' => (int)($row['primary_tag_id'] ?? $row['tag_id']),
        'primaryTagName' => (string)($row['primary_tag_name'] ?? $row['tag_name']),
        'subtagId' => ($row['subtag_id'] ?? null) === null ? null : (int)$row['subtag_id'],
        'subtagName' => $row['subtag_name'] ?? null,
        'archiveUrl' => publicStoragePath((string)$row['archive_path']),
        'archiveOriginalName' => (string)$row['archive_original_name'],
        'archiveSizeBytes' => (int)$row['archive_size_bytes'],
        'imageUrl' => publicStoragePath($row['image_path'] ?? null),
        'imageOriginalName' => $row['image_original_name'] ?? null,
        'createdAt' => (string)$row['created_at'],
        'updatedAt' => (string)$row['updated_at'],
        'viewCount' => (int)($row['view_count'] ?? 0),
        'draft' => toBoolFlag($row['draft'] ?? false),
        'deletedAt' => $row['deleted_at'] ?? null,
        'adminOnly' => materialItemIsAdminOnly($row),
        'webMugenCharacterId' => $row['webmugen_character_id'] ?? null,
        'playUrl' => $row['webmugen_play_url'] ?? null,
        'trialPlayError' => $row['webmugen_error'] ?? null,
        'terms' => array_map(static fn(array $term): array => [
            'id' => (int)$term['id'], 'label' => (string)$term['label'],
            'description' => (string)$term['description'],
            'accepted' => $term['accepted'] === null ? null : (bool)$term['accepted'],
        ], $termStmt->fetchAll(PDO::FETCH_ASSOC)),
        'media' => array_map(static fn(array $media): array => [
            'id' => (int)$media['id'],
            'url' => publicStoragePath((string)$media['path']),
            'originalName' => (string)$media['original_name'],
            'sizeBytes' => (int)$media['size_bytes'],
        ], $mediaStmt->fetchAll(PDO::FETCH_ASSOC)),
    ];
}

function findMaterialItem(PDO $pdo, int $id, bool $includeDeleted = true): ?array
{
    $sql = 'SELECT m.*, t.name AS tag_name, t.parent_id AS tag_parent_id, COALESCE(pt.id, t.id) AS primary_tag_id, COALESCE(pt.name, t.name) AS primary_tag_name, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.id END AS subtag_id, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.name END AS subtag_name, u.icon_path AS user_icon_path, u.login_id AS user_login_id
            FROM material_items m JOIN material_tags t ON t.id = m.tag_id
            LEFT JOIN material_tags pt ON pt.id = t.parent_id
            LEFT JOIN users u ON u.id = m.user_id WHERE m.id = :id';
    if (!$includeDeleted) {
        $sql .= ' AND m.deleted_at IS NULL';
    }
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function createMaterialItem(PDO $pdo): void
{
    ensureMaterialsFrontend();
    $user = optionalUser($pdo);
    $name = normalizeString((string)($_POST['name'] ?? ''));
    $authorName = normalizeString((string)($_POST['author_name'] ?? ''));
    $notes = normalizeString((string)($_POST['notes'] ?? ''));
    $tagId = filter_var($_POST['tag_id'] ?? null, FILTER_VALIDATE_INT);
    $password = trim((string)($_POST['password'] ?? ($user['post_password'] ?? '')));
    $archive = $_FILES['archive'] ?? null;
    $image = $_FILES['image'] ?? null;
    $audioFiles = materialUploadedFiles('audio');
    $draft = toBoolFlag($_POST['draft'] ?? false);
    if ($authorName === '' && $user) {
        $authorName = normalizeString((string)($user['materials_author_name'] ?? $user['display_name'] ?? ''));
    }
    if ($name === '' || $authorName === '' || !$tagId || $password === '') {
        jsonResponse(['success' => false, 'message' => '名称、作者名、タグ、投稿パスワードは必須です。'], 400);
    }
    $tagId = resolveMaterialTagFromRequest($pdo, (int)$tagId);
    validateMaterialUpload($pdo, $archive, 'archive');
    if (is_array($image) && (int)($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        validateMaterialUpload($pdo, $image, 'image');
    }
    foreach ($audioFiles as $audioFile) {
        validateMaterialUpload($pdo, $audioFile, 'audio');
    }
    $now = currentTimestamp();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO material_items
             (user_id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
              image_path, image_original_name, password_hash, draft, created_at, updated_at)
             VALUES (:user_id, :author_name, :name, :notes, :tag_id, "", :archive_original_name, :archive_size_bytes,
              null, null, :password_hash, :draft, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':user_id' => $user ? (int)$user['id'] : null,
            ':author_name' => $authorName, ':name' => $name, ':notes' => $notes, ':tag_id' => (int)$tagId,
            ':archive_original_name' => basename((string)$archive['name']),
            ':archive_size_bytes' => (int)$archive['size'],
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':draft' => $draft ? 1 : 0,
            ':created_at' => $now, ':updated_at' => $now,
        ]);
        $id = (int)$pdo->lastInsertId();
        $archivePath = saveMaterialUpload($archive, $id, 'archive');
        $imagePath = null;
        $imageName = null;
        if (is_array($image) && (int)($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $imagePath = saveMaterialUpload($image, $id, 'image');
            $imageName = basename((string)$image['name']);
        }
        $pdo->prepare('UPDATE material_items SET archive_path = :archive_path, image_path = :image_path, image_original_name = :image_name WHERE id = :id')
            ->execute([':archive_path' => $archivePath, ':image_path' => $imagePath, ':image_name' => $imageName, ':id' => $id]);
        replaceMaterialTerms($pdo, $id, materialTermAnswersFromRequest());
        saveMaterialAudioUploads($pdo, $audioFiles, $id);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
    if (FRONTEND_ID === 'proxy-release') {
        $trialPlay = publishProxyReleaseToWebMugen($pdo, $id, FRONTEND_ID);
        jsonResponse([
            'success' => true,
            'message' => $trialPlay['success']
                ? '公開とWebMUGEN試遊登録が完了しました。'
                : '公開は完了しましたが、WebMUGEN試遊登録に失敗しました。',
            'trialPlay' => $trialPlay,
        ]);
    }
    jsonResponse(['success' => true, 'message' => '素材を登録しました。']);
}

function updateMaterialItem(PDO $pdo): void
{
    ensureMaterialsFrontend();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $item = $id ? findMaterialItem($pdo, (int)$id, false) : null;
    if (!$item) {
        jsonResponse(['success' => false, 'message' => '素材が見つかりません。'], 404);
    }
    requireMaterialPassword($item);
    $name = normalizeString((string)($_POST['name'] ?? $item['name']));
    $authorName = normalizeString((string)($_POST['author_name'] ?? $item['author_name']));
    $notes = normalizeString((string)($_POST['notes'] ?? $item['notes']));
    $tagId = filter_var($_POST['tag_id'] ?? $item['tag_id'], FILTER_VALIDATE_INT);
    if ($name === '' || $authorName === '' || !$tagId) {
        jsonResponse(['success' => false, 'message' => '名称、作者名、タグは必須です。'], 400);
    }
    $tagId = resolveMaterialTagFromRequest($pdo, (int)$tagId);
    $archivePath = (string)$item['archive_path'];
    $archiveName = (string)$item['archive_original_name'];
    $archiveSize = (int)$item['archive_size_bytes'];
    $imagePath = $item['image_path'] ?? null;
    $imageName = $item['image_original_name'] ?? null;
    if (isset($_FILES['archive']) && (int)($_FILES['archive']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        validateMaterialUpload($pdo, $_FILES['archive'], 'archive');
        $archivePath = saveMaterialUpload($_FILES['archive'], (int)$id, 'archive');
        $archiveName = basename((string)$_FILES['archive']['name']);
        $archiveSize = (int)$_FILES['archive']['size'];
    }
    if (isset($_FILES['image']) && (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        validateMaterialUpload($pdo, $_FILES['image'], 'image');
        $imagePath = saveMaterialUpload($_FILES['image'], (int)$id, 'image');
        $imageName = basename((string)$_FILES['image']['name']);
    }
    $audioFiles = materialUploadedFiles('audio');
    $draft = toBoolFlag($_POST['draft'] ?? false);
    foreach ($audioFiles as $audioFile) {
        validateMaterialUpload($pdo, $audioFile, 'audio');
    }
    $pdo->prepare(
        'UPDATE material_items SET author_name = :author_name, name = :name, notes = :notes, tag_id = :tag_id,
         archive_path = :archive_path, archive_original_name = :archive_name, archive_size_bytes = :archive_size,
         image_path = :image_path, image_original_name = :image_name, draft = :draft, updated_at = :updated_at WHERE id = :id'
    )->execute([
        ':author_name' => $authorName, ':name' => $name, ':notes' => $notes, ':tag_id' => (int)$tagId,
        ':archive_path' => $archivePath, ':archive_name' => $archiveName, ':archive_size' => $archiveSize,
        ':image_path' => $imagePath, ':image_name' => $imageName, ':draft' => toBoolFlag($_POST['draft'] ?? ($item['draft'] ?? false)) ? 1 : 0, ':updated_at' => currentTimestamp(), ':id' => (int)$id,
    ]);
    if (isset($_POST['terms'])) {
        replaceMaterialTerms($pdo, (int)$id, materialTermAnswersFromRequest());
    }
    saveMaterialAudioUploads($pdo, $audioFiles, (int)$id);
    if (FRONTEND_ID === 'proxy-release') {
        $trialPlay = publishProxyReleaseToWebMugen($pdo, (int)$id, FRONTEND_ID);
        jsonResponse([
            'success' => true,
            'message' => $trialPlay['success']
                ? '更新とWebMUGEN試遊登録が完了しました。'
                : '更新は完了しましたが、WebMUGEN試遊登録に失敗しました。',
            'trialPlay' => $trialPlay,
        ]);
    }
    jsonResponse(['success' => true, 'message' => '素材を更新しました。']);
}

function publishProxyReleaseToWebMugen(PDO $pdo, int $itemId, string $frontendId, ?callable $requester = null): array
{
    if ($frontendId !== 'proxy-release') return ['success' => false, 'skipped' => true, 'code' => 'frontend.not_proxy_release'];
    $item = findMaterialItem($pdo, $itemId, false);
    if (!$item) return ['success' => false, 'code' => 'publication.not_found', 'message' => 'Proxy publication was not found.'];
    $archiveFile = basename(str_replace('\\', '/', (string)$item['archive_path']));
    $settings = loadSettings($pdo);
    $storedSecret = trim((string)($settings['security']['webMugenApiToken'] ?? ''));
    $secret = $storedSecret !== '' ? $storedSecret : trim((string)(getenv('THREADFORGE_WEBMUGEN_CATALOG_SECRET') ?: ''));
    if ($secret === '') return saveWebMugenTrialFailure($pdo, $itemId, 'config.secret_missing', 'WebMUGEN Catalog API secret is not configured.');
    try {
        $endpoint = webMugenCatalogEndpoint($settings, $_SERVER);
    } catch (InvalidArgumentException $error) {
        return saveWebMugenTrialFailure($pdo, $itemId, 'config.url_invalid', $error->getMessage());
    }
    $stageId = trim((string)($settings['config']['webMugenStageId'] ?? 'cyber'));
    $payload = json_encode([
        'publicationId' => (string)$itemId,
        'archiveFile' => $archiveFile,
        'stageId' => $stageId,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $requester ??= static fn(string $url, array $headers, string $body): array => httpRawRequest('POST', $url, $headers, $body);
    $result = $requester($endpoint, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret,
        'X-WebMUGEN-Token: ' . $secret,
    ], (string)$payload);
    $body = is_array($result['body'] ?? null) ? $result['body'] : [];
    if (!($result['success'] ?? false) || !($body['success'] ?? false) || !is_string($body['characterId'] ?? null) || !is_string($body['characterPath'] ?? null) || !is_string($body['playUrl'] ?? null)) {
        $message = (string)($result['message'] ?? $body['error']['message'] ?? 'WebMUGEN Catalog API returned an invalid response.');
        $code = (string)($body['error']['code'] ?? 'api.failed');
        return saveWebMugenTrialFailure($pdo, $itemId, $code, $message);
    }
    $pdo->prepare('UPDATE material_items SET webmugen_character_id = :character_id, webmugen_play_url = :play_url, webmugen_error = null WHERE id = :id')
        ->execute([':character_id' => $body['characterId'], ':play_url' => $body['playUrl'], ':id' => $itemId]);
    return ['success' => true, 'characterId' => $body['characterId'], 'characterPath' => $body['characterPath'], 'playUrl' => $body['playUrl']];
}

function saveWebMugenTrialFailure(PDO $pdo, int $itemId, string $code, string $message): array
{
    $detail = json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $pdo->prepare('UPDATE material_items SET webmugen_character_id = null, webmugen_play_url = null, webmugen_error = :error WHERE id = :id')
        ->execute([':error' => $detail, ':id' => $itemId]);
    return ['success' => false, 'code' => $code, 'message' => $message];
}

function webMugenCatalogEndpoint(array $settings, array $server): string
{
    $configured = trim((string)($settings['config']['webMugenApiUrl'] ?? ''));
    if ($configured === '') $configured = trim((string)(getenv('THREADFORGE_WEBMUGEN_CATALOG_API_URL') ?: ''));
    return webMugenCatalogEndpointFromValue($configured, $server);
}

function webMugenCatalogEndpointFromValue(string $configured, array $server): string
{
    $scheme = (($server['HTTPS'] ?? '') !== '' && ($server['HTTPS'] ?? '') !== 'off') ? 'https' : 'http';
    $host = (string)($server['HTTP_HOST'] ?? 'localhost');
    $url = trim($configured) !== '' ? trim($configured) : $scheme . '://' . $host . '/DotoEita/50_WEBMUGEN/api/catalog.php';
    $parts = parse_url($url);
    $serverHost = parse_url($scheme . '://' . $host, PHP_URL_HOST);
    if (
        !is_array($parts)
        || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
        || strcasecmp((string)($parts['host'] ?? ''), (string)$serverHost) !== 0
        || !str_ends_with((string)($parts['path'] ?? ''), '/api/catalog.php')
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['fragment'])
    ) {
        throw new InvalidArgumentException('WebMUGEN API URLは同一ホストの /api/catalog.php を指定してください。');
    }
    $query = [];
    parse_str((string)($parts['query'] ?? ''), $query);
    $query['action'] = 'publish-character';
    $authority = (string)$parts['host'] . (isset($parts['port']) ? ':' . (int)$parts['port'] : '');
    return (string)$parts['scheme'] . '://' . $authority . (string)$parts['path'] . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function deleteMaterialItem(PDO $pdo): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $item = $id ? findMaterialItem($pdo, (int)$id, false) : null;
    if (!$item) {
        jsonResponse(['success' => false, 'message' => '素材が見つかりません。'], 404);
    }
    requireMaterialPassword($item);
    $pdo->prepare('UPDATE material_items SET deleted_at = :deleted_at WHERE id = :id')
        ->execute([':deleted_at' => currentTimestamp(), ':id' => (int)$id]);
    jsonResponse(['success' => true, 'message' => '素材を削除しました。']);
}

function verifyMaterialPassword(PDO $pdo): void
{
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $item = $id ? findMaterialItem($pdo, (int)$id, false) : null;
    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Material item was not found.'], 404);
    }
    requireMaterialPassword($item);
    jsonResponse(['success' => true, 'message' => 'OK']);
}

function requireMaterialPassword(array $item): void
{
    if (materialItemIsAdminOnly($item)) {
        jsonResponse(['success' => false, 'message' => 'この素材は投稿パスワードが設定されていないため、管理画面からのみ変更できます。'], 403);
    }
    $password = trim((string)($_POST['password'] ?? ''));
    if ($password === '' || !password_verify($password, (string)$item['password_hash'])) {
        jsonResponse(['success' => false, 'message' => '投稿パスワードが違います。'], 403);
    }
}

function materialItemIsAdminOnly(array $item): bool
{
    return trim((string)($item['legacy_source'] ?? '')) !== ''
        || trim((string)($item['password_hash'] ?? '')) === '';
}

function listDeletedMaterialItems(PDO $pdo): void
{
    requireAdmin();
    $stmt = $pdo->query(
        'SELECT m.*, t.name AS tag_name, t.parent_id AS tag_parent_id, COALESCE(pt.id, t.id) AS primary_tag_id, COALESCE(pt.name, t.name) AS primary_tag_name, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.id END AS subtag_id, CASE WHEN t.parent_id IS NULL THEN NULL ELSE t.name END AS subtag_name, u.icon_path AS user_icon_path, u.login_id AS user_login_id
         FROM material_items m JOIN material_tags t ON t.id = m.tag_id
         LEFT JOIN material_tags pt ON pt.id = t.parent_id
         LEFT JOIN users u ON u.id = m.user_id WHERE m.deleted_at IS NOT NULL ORDER BY m.deleted_at DESC, m.id DESC'
    );
    jsonResponse(['success' => true, 'items' => array_map(
        fn(array $row): array => buildMaterialItem($pdo, $row),
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    )]);
}

function adminDeleteMaterialItems(PDO $pdo): void
{
    requireAdmin();
    $ids = materialIdsFromRequest();
    $stmt = $pdo->prepare('UPDATE material_items SET deleted_at = :deleted_at WHERE id = :id');
    foreach ($ids as $id) {
        $stmt->execute([':deleted_at' => currentTimestamp(), ':id' => $id]);
    }
    jsonResponse(['success' => true, 'message' => count($ids) . '件を削除しました。']);
}

function restoreMaterialItems(PDO $pdo): void
{
    requireAdmin();
    $ids = materialIdsFromRequest();
    $stmt = $pdo->prepare('UPDATE material_items SET deleted_at = null WHERE id = :id');
    foreach ($ids as $id) {
        $stmt->execute([':id' => $id]);
    }
    jsonResponse(['success' => true, 'message' => count($ids) . '件を復元しました。']);
}

function purgeMaterialItems(PDO $pdo): void
{
    requireAdmin();
    $ids = materialIdsFromRequest();
    foreach ($ids as $id) {
        $item = findMaterialItem($pdo, $id);
        if (!$item || ($item['deleted_at'] ?? null) === null) {
            continue;
        }
        foreach (['archive_path', 'image_path'] as $column) {
            $path = (string)($item[$column] ?? '');
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
        $mediaStmt = $pdo->prepare('SELECT path FROM material_media WHERE item_id = :id');
        $mediaStmt->execute([':id' => $id]);
        foreach ($mediaStmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
        $pdo->prepare('DELETE FROM material_media WHERE item_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM material_item_terms WHERE item_id = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM material_items WHERE id = :id')->execute([':id' => $id]);
    }
    jsonResponse(['success' => true, 'message' => '選択した削除済み素材を完全消去しました。']);
}

function saveMaterialCatalog(PDO $pdo): void
{
    requireAdmin();
    $tags = json_decode((string)($_POST['tags'] ?? '[]'), true);
    $terms = json_decode((string)($_POST['terms'] ?? '[]'), true);
    if (!is_array($tags) || !is_array($terms)) {
        jsonResponse(['success' => false, 'message' => 'タグまたは利用規約の形式が正しくありません。'], 400);
    }
    $pdo->beginTransaction();
    try {
        $seenTags = upsertMaterialTags($pdo, $tags);
        if ($seenTags === []) {
            throw new RuntimeException('タグを1件以上登録してください。');
        }
        deleteUnusedCatalogRows($pdo, 'material_tags', 'tag_id', 'material_items', $seenTags);
        $seenTerms = upsertMaterialTerms($pdo, $terms);
        deleteUnusedCatalogRows($pdo, 'material_terms', 'term_id', 'material_item_terms', $seenTerms);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 400);
    }
    jsonResponse(['success' => true, 'message' => 'タグと利用規約を保存しました。']);
}

function upsertMaterialTags(PDO $pdo, array $tags): array
{
    $seen = [];
    foreach ($tags as $index => $tag) {
        $name = normalizeString((string)($tag['name'] ?? ''));
        if ($name === '') continue;
        $id = filter_var($tag['id'] ?? null, FILTER_VALIDATE_INT);
        $parentIdRaw = $tag['parentId'] ?? $tag['parent_id'] ?? null;
        $parentId = filter_var($parentIdRaw, FILTER_VALIDATE_INT);
        if ($parentId !== false && $parentId !== null && $id && (int)$parentId === (int)$id) {
            $parentId = null;
        }
        if ($id) {
            $pdo->prepare('UPDATE material_tags SET name = :name, parent_id = :parent_id, sort_order = :sort_order WHERE id = :id')
                ->execute([':name' => $name, ':parent_id' => $parentId ?: null, ':sort_order' => $index, ':id' => (int)$id]);
            $seen[] = (int)$id;
        } else {
            $pdo->prepare('INSERT INTO material_tags (name, parent_id, sort_order, created_at) VALUES (:name, :parent_id, :sort_order, :created_at)')
                ->execute([':name' => $name, ':parent_id' => $parentId ?: null, ':sort_order' => $index, ':created_at' => currentTimestamp()]);
            $seen[] = (int)$pdo->lastInsertId();
        }
    }
    return $seen;
}

function upsertMaterialTerms(PDO $pdo, array $terms): array
{
    $seen = [];
    foreach ($terms as $index => $term) {
        $label = normalizeString((string)($term['label'] ?? ''));
        if ($label === '') continue;
        $description = normalizeString((string)($term['description'] ?? ''));
        $id = filter_var($term['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare('UPDATE material_terms SET label = :label, description = :description, sort_order = :sort_order WHERE id = :id')
                ->execute([':label' => $label, ':description' => $description, ':sort_order' => $index, ':id' => (int)$id]);
            $seen[] = (int)$id;
        } else {
            $pdo->prepare('INSERT INTO material_terms (label, description, sort_order, created_at) VALUES (:label, :description, :sort_order, :created_at)')
                ->execute([':label' => $label, ':description' => $description, ':sort_order' => $index, ':created_at' => currentTimestamp()]);
            $seen[] = (int)$pdo->lastInsertId();
        }
    }
    return $seen;
}

function deleteUnusedCatalogRows(PDO $pdo, string $table, string $referenceColumn, string $referenceTable, array $ids): void
{
    if ($ids === []) {
        $pdo->exec("DELETE FROM {$table} WHERE id NOT IN (SELECT DISTINCT {$referenceColumn} FROM {$referenceTable})");
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM {$table} WHERE id NOT IN ({$placeholders}) AND id NOT IN (SELECT DISTINCT {$referenceColumn} FROM {$referenceTable})")
        ->execute($ids);
}

function assignMaterialAuthor(PDO $pdo): void
{
    requireAdmin();
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $userIdRaw = trim((string)($_POST['user_id'] ?? ''));
    $userId = $userIdRaw === '' ? null : filter_var($userIdRaw, FILTER_VALIDATE_INT);
    if (!$id || ($userIdRaw !== '' && !$userId)) {
        jsonResponse(['success' => false, 'message' => '素材IDまたは作者IDが正しくありません。'], 400);
    }
    if ($userId && !findUserById($pdo, (int)$userId)) {
        jsonResponse(['success' => false, 'message' => '指定したユーザーが見つかりません。'], 404);
    }
    $authorName = normalizeString((string)($_POST['author_name'] ?? ''));
    $fields = 'user_id = :user_id';
    $params = [':user_id' => $userId ? (int)$userId : null, ':id' => (int)$id, ':updated_at' => currentTimestamp()];
    if ($authorName !== '') {
        $fields .= ', author_name = :author_name';
        $params[':author_name'] = $authorName;
    }
    $pdo->prepare('UPDATE material_items SET ' . $fields . ', updated_at = :updated_at WHERE id = :id')->execute($params);
    jsonResponse(['success' => true, 'message' => '素材の作者IDを更新しました。']);
}

function materialAnalytics(PDO $pdo): void
{
    requireAdmin();
    $summary = $pdo->query(
        'SELECT COUNT(*) AS total_items, COALESCE(SUM(archive_size_bytes), 0) AS total_bytes,
         COALESCE(SUM(view_count), 0) AS total_views,
         COUNT(DISTINCT tag_id) AS used_tags,
         COUNT(DISTINCT CASE WHEN user_id IS NULL THEN "guest:" || author_name ELSE "user:" || user_id END) AS authors
         FROM material_items WHERE deleted_at IS NULL'
    )->fetch(PDO::FETCH_ASSOC);
    $months = $pdo->query(
        'SELECT substr(created_at, 1, 7) AS month, COUNT(*) AS count, SUM(archive_size_bytes) AS size_bytes
         FROM material_items GROUP BY substr(created_at, 1, 7) ORDER BY month DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'summary' => $summary, 'months' => $months]);
}

function recordMaterialView(PDO $pdo): void
{
    ensureMaterialsFrontend();
    $id = filter_var($_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        jsonResponse(['success' => false, 'message' => '記事IDが正しくありません。'], 400);
    }
    $stmt = $pdo->prepare('UPDATE material_items SET view_count = view_count + 1 WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute([':id' => (int)$id]);
    $select = $pdo->prepare('SELECT view_count FROM material_items WHERE id = :id');
    $select->execute([':id' => (int)$id]);
    jsonResponse(['success' => true, 'view_count' => (int)$select->fetchColumn()]);
}

function updateMaterialProfile(PDO $pdo): void
{
    $user = requireUser($pdo);
    $authorName = normalizeString((string)($_POST['author_name'] ?? $user['display_name']));
    $postPassword = normalizeString((string)($_POST['post_password'] ?? $user['post_password']));
    $defaults = json_decode((string)($_POST['default_terms'] ?? '{}'), true);
    if ($authorName === '' || !is_array($defaults)) {
        jsonResponse(['success' => false, 'message' => '作者名または利用規約の初期値が正しくありません。'], 400);
    }
    $iconPath = $user['icon_path'] ?? null;
    if (isset($_FILES['icon']) && (int)($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $iconPath = saveUploadedUserIcon($_FILES['icon'], (int)$user['id']);
    }
    $pdo->prepare(
        'UPDATE users SET post_password = :post_password, materials_author_name = :author_name,
         materials_default_terms = :default_terms,
         icon_path = :icon_path, updated_at = :updated_at WHERE id = :id'
    )->execute([
        ':post_password' => $postPassword,
        ':author_name' => $authorName,
        ':default_terms' => json_encode($defaults, JSON_UNESCAPED_UNICODE),
        ':icon_path' => $iconPath, ':updated_at' => currentTimestamp(), ':id' => (int)$user['id'],
    ]);
    jsonResponse(['success' => true, 'user' => buildUser(findUserById($pdo, (int)$user['id']))]);
}

function ensureMaterialsFrontend(): void
{
    if (!in_array(FRONTEND_ID, ['materials-library', 'proxy-release', 'document-holder'], true) && !isPackagedSingleFrontendApp()) {
        jsonResponse(['success' => false, 'message' => 'このAPIはmaterials-library/proxy-release専用です。'], 403);
    }
}

function requireMaterialTag(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM material_tags WHERE id = :id');
    $stmt->execute([':id' => $id]);
    if ((int)$stmt->fetchColumn() === 0) {
        jsonResponse(['success' => false, 'message' => '指定したタグが見つかりません。'], 400);
    }
}

function resolveMaterialTagFromRequest(PDO $pdo, int $tagId): int
{
    requireMaterialTag($pdo, $tagId);
    $subtagId = filter_var($_POST['subtag_id'] ?? null, FILTER_VALIDATE_INT);
    if ($subtagId) {
        requireMaterialTag($pdo, (int)$subtagId);
        return (int)$subtagId;
    }
    $newSubtag = normalizeString((string)($_POST['new_subtag_name'] ?? ''));
    if ($newSubtag === '') {
        return $tagId;
    }

    $parentStmt = $pdo->prepare('SELECT parent_id FROM material_tags WHERE id = :id');
    $parentStmt->execute([':id' => $tagId]);
    $parentId = $parentStmt->fetchColumn();
    $parent = $parentId === false || $parentId === null ? $tagId : (int)$parentId;

    $existing = $pdo->prepare('SELECT id FROM material_tags WHERE parent_id = :parent_id AND name = :name LIMIT 1');
    $existing->execute([':parent_id' => $parent, ':name' => $newSubtag]);
    $existingId = $existing->fetchColumn();
    if ($existingId) {
        return (int)$existingId;
    }
    $sortStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM material_tags WHERE parent_id = :parent_id');
    $sortStmt->execute([':parent_id' => $parent]);
    $sortOrder = (int)$sortStmt->fetchColumn();
    $insert = $pdo->prepare('INSERT INTO material_tags (name, parent_id, sort_order, created_at) VALUES (:name, :parent_id, :sort_order, :created_at)');
    $insert->execute([':name' => $newSubtag, ':parent_id' => $parent, ':sort_order' => $sortOrder, ':created_at' => currentTimestamp()]);
    return (int)$pdo->lastInsertId();
}

function validateMaterialUpload(PDO $pdo, mixed $file, string $kind): void
{
    if (!is_array($file)) {
        jsonResponse(['success' => false, 'message' => $kind === 'archive' ? '素材ファイルを選択してください。' : '説明画像を受信できませんでした。'], 400);
    }

    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        jsonResponse([
            'success' => false,
            'message' => 'ファイルがPHPのアップロード上限を超えています。upload_max_filesize=' . ini_get('upload_max_filesize') . ' / post_max_size=' . ini_get('post_max_size'),
        ], 400);
    }
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        jsonResponse(['success' => false, 'message' => $kind === 'archive' ? '素材ファイルを選択してください。' : '説明画像を選択してください。'], 400);
    }
    if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        jsonResponse(['success' => false, 'message' => 'ファイルの受信に失敗しました。アップロードをやり直してください。'], 400);
    }

    $config = loadSettings($pdo)['config'] ?? [];
    $extension = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if ($kind === 'archive') {
        $allowed = preg_split('/[\s,;]+/', strtolower((string)($config['materialsAllowedArchiveExtensions'] ?? 'zip 7z rar'))) ?: [];
        $limit = max(1, (int)($config['materialsMaxArchiveKb'] ?? 102400)) * 1024;
    } elseif ($kind === 'audio') {
        $allowed = ['mp3'];
        $limit = max(1, (int)($config['materialsMaxImageKb'] ?? 10240)) * 1024;
    } else {
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $limit = max(1, (int)($config['materialsMaxImageKb'] ?? 10240)) * 1024;
    }
    if (!in_array($extension, $allowed, true)) {
        jsonResponse(['success' => false, 'message' => '許可されていないファイル形式です。'], 400);
    }
    if ((int)$file['size'] > $limit) {
        jsonResponse(['success' => false, 'message' => 'ファイルサイズが上限を超えています。'], 400);
    }
}
function materialUploadedFiles(string $key): array
{
    $files = $_FILES[$key] ?? null;
    if (!is_array($files)) {
        return [];
    }
    if (!is_array($files['name'] ?? null)) {
        return (int)($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE ? [] : [$files];
    }
    $result = [];
    foreach ($files['name'] as $index => $name) {
        $error = (int)($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $result[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => $files['size'][$index] ?? 0,
        ];
    }
    return $result;
}

function saveMaterialAudioUploads(PDO $pdo, array $files, int $itemId): void
{
    if ($files === []) {
        return;
    }
    $sortOrderStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM material_media WHERE item_id = :item_id');
    $sortOrderStmt->execute([':item_id' => $itemId]);
    $sortOrder = (int)$sortOrderStmt->fetchColumn();
    $insert = $pdo->prepare(
        'INSERT INTO material_media (item_id, path, original_name, size_bytes, sort_order, created_at)
         VALUES (:item_id, :path, :original_name, :size_bytes, :sort_order, :created_at)'
    );
    foreach ($files as $file) {
        $destination = STORAGE_DIR . '/material-' . $itemId . '-audio-' . $sortOrder . '.mp3';
        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            throw new RuntimeException('MP3ファイルを保存できませんでした。');
        }
        $insert->execute([
            ':item_id' => $itemId,
            ':path' => $destination,
            ':original_name' => basename((string)$file['name']),
            ':size_bytes' => (int)$file['size'],
            ':sort_order' => $sortOrder,
            ':created_at' => currentTimestamp(),
        ]);
        $sortOrder++;
    }
}

function saveMaterialUpload(array $file, int $id, string $kind): string
{
    $extension = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $name = 'material-' . $id . '-' . $kind . '.' . $extension;
    $destination = STORAGE_DIR . '/' . $name;
    if (is_file($destination)) {
        @rename($destination, STORAGE_DIR . '/material-' . $id . '-' . $kind . '-' . date('YmdHis') . '.' . $extension);
    }
    if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
        jsonResponse(['success' => false, 'message' => 'ファイルを保存できませんでした。'], 500);
    }
    return $destination;
}

function materialTermAnswersFromRequest(): array
{
    $answers = json_decode((string)($_POST['terms'] ?? '{}'), true);
    return is_array($answers) ? $answers : [];
}

function replaceMaterialTerms(PDO $pdo, int $itemId, array $answers): void
{
    $pdo->prepare('DELETE FROM material_item_terms WHERE item_id = :item_id')->execute([':item_id' => $itemId]);
    $insert = $pdo->prepare('INSERT INTO material_item_terms (item_id, term_id, accepted) VALUES (:item_id, :term_id, :accepted)');
    foreach ($answers as $rawId => $accepted) {
        $id = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($id && $accepted !== null) {
            $insert->execute([':item_id' => $itemId, ':term_id' => (int)$id, ':accepted' => toBoolFlag($accepted) ? 1 : 0]);
        }
    }
}

function materialIdsFromRequest(): array
{
    $raw = $_POST['ids'] ?? [];
    if (is_string($raw)) {
        $raw = explode(',', $raw);
    }
    $ids = array_values(array_unique(array_filter(array_map(
        static fn($value): int => (int)(filter_var($value, FILTER_VALIDATE_INT) ?: 0),
        is_array($raw) ? $raw : []
    ))));
    if ($ids === []) {
        jsonResponse(['success' => false, 'message' => '対象を選択してください。'], 400);
    }
    return $ids;
}

function defaultSettings(): array
{
    return [
        'config' => [
            'bbsTitle' => 'ThreadForge',
            'homePageUrl' => '/',
            'uploaderHomePageUrl' => '../',
            'materialsTitle' => materialDefaultTitle(),
            'materialsDescription' => materialDefaultDescription(),
            'materialsDescriptionBannerEnabled' => materialDefaultDescriptionBannerEnabled(),
            'materialsDescriptionBannerImageUrl' => materialDefaultDescriptionBannerImageUrl(),
            'materialsDescriptionBannerLinkUrl' => '',
            'materialsDescriptionBannerAlt' => materialDefaultDescriptionBannerAlt(),
            'materialsDescriptionBannerIntervalMs' => 5000,
            'materialsDescriptionBanners' => materialDefaultDescriptionBanners(),
            'materialsHomePageUrl' => '../',
            'proxyTrialPlayUrl' => '',
            'webMugenApiUrl' => '',
            'webMugenStageId' => 'cyber',
            'materialsManualBody' => materialsDefaultManualBody(),
            'materialsGroupParent' => 'tag',
            'materialsMaxArchiveKb' => 102400,
            'materialsMaxImageKb' => 10240,
            'materialsAllowedArchiveExtensions' => materialDefaultArchiveExtensions(),
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
        'skin' => array_merge(imageBoardDefaultSkin(), [
            'materialsPageBackgroundColor' => '#050505',
            'materialsPageTextColor' => '#f2f2f2',
            'materialsHeaderBackgroundColor' => '#000000',
            'materialsHeaderTextColor' => '#ffffff',
            'materialsHeaderBorderColor' => '#222222',
            'materialsPanelBackgroundColor' => '#111820',
            'materialsPanelBorderColor' => '#6c7787',
            'materialsHeadingBackgroundColor' => '#59636f',
            'materialsHeadingTextColor' => '#ffffff',
            'materialsAccentColor' => '#8fb0ff',
            'materialsButtonBackgroundColor' => '#3974ee',
            'materialsButtonTextColor' => '#ffffff',
            'materialsSecondaryButtonBackgroundColor' => '#303640',
            'materialsSecondaryButtonTextColor' => '#ffffff',
            'materialsDangerButtonBackgroundColor' => '#c44141',
            'materialsDangerButtonTextColor' => '#ffffff',
            'materialsInputBackgroundColor' => '#282f38',
            'materialsInputTextColor' => '#ffffff',
            'materialsImageBackgroundColor' => '#020202',
            'materialsMutedTextColor' => '#aab4c0',
            'materialsPositiveColor' => '#72e9a2',
            'materialsNegativeColor' => '#ff9292',
            'materialsUnknownColor' => '#f0cf78',
        ]),
        'security' => [
            'adminPasswordHash' => '',
            'cronApiKey' => '',
            'webMugenApiToken' => '',
        ],
    ];
}

function imageBoardDefaultSkin(): array
{
    return [
        'normalFrameColor' => '#858f9b',
        'gdgdFrameColor' => '#6e9f8c',
        'backgroundColor' => '#0b0d10',
        'pageTextColor' => '#eef1f4',
        'linkColor' => '#8fb7e8',
        'panelBackgroundColor' => '#151b22',
        'panelTitleBackgroundColor' => '#59636f',
        'panelBorderColor' => '#707b88',
        'labelColor' => '#aac4df',
        'inputBackgroundColor' => '#2d333b',
        'inputTextColor' => '#ffffff',
        'buttonBackgroundColor' => '#3974ee',
        'buttonTextColor' => '#ffffff',
        'buttonBorderColor' => '#8fb0ff',
        'secondaryButtonBackgroundColor' => '#303640',
        'secondaryButtonTextColor' => '#ffffff',
        'secondaryButtonBorderColor' => '#778291',
        'normalHeaderColor' => '#626c78',
        'normalTextColor' => '#ffffff',
        'gdgdHeaderColor' => '#477968',
        'gdgdTextColor' => '#ffffff',
        'replyBorderColor' => '#737d89',
        'quickReactionButtonBackgroundColor' => '#303640',
        'dangerColor' => '#ff8585',
        'warningColor' => '#f0cf78',
        'successColor' => '#8bd39a',
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

function socialDebugLog(string $message, array $data = [], string $level = 'info'): void
{
    socialDebugLogLine($message, $data, $level);
}

function socialDebugLogResult(string $message, array $result): void
{
    $level = !empty($result['success']) ? 'info' : 'error';
    socialDebugLogLine($message, sanitizeSocialLogData($result), $level);
}

function socialDebugLogLine(string $message, array $data = [], string $level = 'info'): void
{
    $logFile = __DIR__ . '/social_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($level) . '] ' . $message;
    if ($data !== []) {
        $line .= ' ' . json_encode(sanitizeSocialLogData($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
    if (strpos($message, 'mastodon') === 0) {
        file_put_contents(__DIR__ . '/mastodon_debug.log', $line, FILE_APPEND);
    }
}

function sanitizeSocialLogData($value)
{
    if (is_array($value)) {
        $sanitized = [];
        foreach ($value as $key => $item) {
            $keyString = strtolower((string)$key);
            if (strpos($keyString, 'token') !== false || strpos($keyString, 'secret') !== false || strpos($keyString, 'password') !== false || $keyString === 'i') {
                $sanitized[$key] = '[hidden]';
            } else {
                $sanitized[$key] = sanitizeSocialLogData($item);
            }
        }
        return $sanitized;
    }
    return $value;
}
