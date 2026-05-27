<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiHttpIntegrationTest extends TestCase
{
    private $serverProcess = null;
    private int $serverProcessId = 0;
    private string $baseUrl = '';

    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The curl extension is required for HTTP integration tests.');
        }

        $this->resetRuntime();
        $this->startServer();
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        $this->resetRuntime();
    }

    public function testCreateUpdateAndDeletePostThroughHttpSoftDeletesRow(): void
    {
        $created = $this->postForm([
            'action' => 'createPost',
            'name' => 'Alice',
            'title' => 'Original title',
            'message' => 'Original body',
            'password' => 'secret',
        ]);

        $this->assertSame(200, $created['status']);
        $this->assertTrue($created['json']['success']);

        $post = $this->latestPost();
        $id = (int)$post['id'];
        $this->assertSame($id, (int)$post['thread_id']);
        $this->assertNull($post['deleted_at']);

        $updated = $this->postForm([
            'action' => 'updatePost',
            'id' => (string)$id,
            'name' => 'Alice Updated',
            'title' => 'Updated title',
            'message' => 'Updated body',
            'password' => 'secret',
        ]);

        $this->assertSame(200, $updated['status']);
        $this->assertTrue($updated['json']['success']);

        $fetched = $this->getJson(['action' => 'getPost', 'id' => (string)$id]);
        $this->assertSame(200, $fetched['status']);
        $this->assertSame('Alice Updated', $fetched['json']['name']);
        $this->assertSame('Updated title', $fetched['json']['title']);
        $this->assertSame('Updated body', $fetched['json']['message']);
        $this->assertSame(1, $fetched['json']['revision_count']);
        $this->assertCount(1, $fetched['json']['revision_dates']);

        $deleted = $this->postForm([
            'action' => 'deletePost',
            'id' => (string)$id,
            'password' => 'secret',
        ]);

        $this->assertSame(200, $deleted['status']);
        $this->assertTrue($deleted['json']['success']);

        $row = $this->postById($id);
        $this->assertIsArray($row);
        $this->assertNotNull($row['deleted_at']);
        $this->assertSame(1, $this->postCountIncludingDeleted());

        $missing = $this->getJson(['action' => 'getPost', 'id' => (string)$id]);
        $this->assertSame(404, $missing['status']);
        $this->assertFalse($missing['json']['success']);
    }

    public function testAdminSettingsStayClosedWhenNoPasswordIsConfigured(): void
    {
        $status = $this->getJson(['action' => 'adminStatus']);
        $this->assertSame(200, $status['status']);
        $this->assertFalse($status['json']['adminPasswordConfigured']);

        $denied = $this->getJson(['action' => 'getSettings', 'admin_password' => 'wrong']);
        $this->assertSame(403, $denied['status']);
        $this->assertFalse($denied['json']['success']);

        $defaultPassword = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin']);
        $this->assertSame(403, $defaultPassword['status']);
        $this->assertFalse($defaultPassword['json']['success']);

        $this->setAdminPassword('admin-secret');
        $allowed = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin-secret']);
        $this->assertSame(200, $allowed['status']);
        $this->assertTrue($allowed['json']['success']);
        $this->assertTrue($allowed['json']['system']['adminPasswordConfigured']);
    }

    public function testInitializeAdminPasswordAllowsFirstBrowserSetupOnly(): void
    {
        $created = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'first-secret',
        ]);
        $this->assertSame(200, $created['status']);
        $this->assertTrue($created['json']['success']);

        $allowed = $this->getJson(['action' => 'getSettings', 'admin_password' => 'first-secret']);
        $this->assertSame(200, $allowed['status']);
        $this->assertTrue($allowed['json']['system']['adminPasswordConfigured']);

        $second = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'second-secret',
        ]);
        $this->assertSame(409, $second['status']);
        $this->assertFalse($second['json']['success']);
    }

    public function testFreshDatabaseDefaultsAndArchivePeriodFiltering(): void
    {
        $public = $this->getJson(['action' => 'publicSettings']);
        $this->assertSame(200, $public['status']);
        $this->assertFalse($public['json']['settings']['config']['gdgdEnabled']);
        $this->assertSame('#art', $public['json']['settings']['config']['socialHashtags']);
        $this->assertArrayNotHasKey('logView', $public['json']['settings']['config']);
        $this->assertSame('number', $public['json']['settings']['config']['listOrder']);

        $this->setAdminPassword('admin-secret');
        $settings = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin-secret']);
        $this->assertSame(200, $settings['status']);
        $config = $settings['json']['settings']['config'];
        $skin = $settings['json']['settings']['skin'];
        $this->assertArrayNotHasKey('logView', $config);
        $this->assertSame('number', $config['listOrder']);
        $this->assertSame('#7f00a8', $skin['normalHeaderColor']);
        $this->assertSame('#39988a', $skin['gdgdHeaderColor']);
        $this->assertSame('#30343b', $skin['quickReactionButtonBackgroundColor']);
        $this->assertSame('#ffd36a', $skin['warningColor']);
        $this->assertSame('', $config['tweetBaseUrl']);
        $this->assertSame('', $config['tweetConsumerKey']);
        $this->assertSame('', $config['tweetConsumerSecret']);
        $this->assertSame('', $config['tweetAccessToken']);
        $this->assertSame('', $config['tweetAccessTokenSecret']);
        $this->assertSame('', $config['blueskyServiceUrl']);
        $this->assertSame('', $config['blueskyPublicApiUrl']);
        $this->assertSame('', $config['blueskyHandle']);
        $this->assertSame('', $config['blueskyAppPassword']);
        $this->assertSame('', $config['mastodonInstanceUrl']);
        $this->assertSame('', $config['mastodonAccessToken']);
        $this->assertSame('', $config['mastodonVisibility']);
        $this->assertSame('', $config['misskeyInstanceUrl']);
        $this->assertSame('', $config['misskeyAccessToken']);

        $this->insertPost('User', 'May 2025', 'Body', '2025-05-01 12:00:00');
        $this->insertPost('User', 'June 2025', 'Body', '2025-06-01 12:00:00');
        $this->insertPost('User', 'May 2026', 'Body', '2026-05-01 12:00:00');

        $allPosts = $this->getJson(['action' => 'listThreads']);
        $this->assertSame(200, $allPosts['status']);
        $this->assertCount(3, $allPosts['json']);

        $filtered = $this->getJson(['action' => 'listThreads', 'years' => '2025', 'months' => '2026-05']);
        $this->assertSame(200, $filtered['status']);
        $this->assertCount(3, $filtered['json']);

        $monthOnly = $this->getJson(['action' => 'listThreads', 'months' => '2025-06']);
        $this->assertSame(200, $monthOnly['status']);
        $this->assertCount(1, $monthOnly['json']);
        $this->assertSame('June 2025', $monthOnly['json'][0]['title']);

        $meta = $this->getJson(['action' => 'listThreadArchiveMeta', 'months' => '2025-06']);
        $this->assertSame(200, $meta['status']);
        $this->assertTrue($meta['json']['success']);
        $this->assertSame(1, $meta['json']['total']);
        $this->assertGreaterThanOrEqual(3, count($meta['json']['periods']));
    }

    public function testThreadListOrderCanSwitchBetweenNumberAndCreatedDate(): void
    {
        $this->insertPost('User', 'Older No with newer date', 'Body 1', '2026-05-20 12:00:00');
        $this->insertPost('User', 'Newer No with older date', 'Body 2', '2026-05-01 12:00:00');

        $numberOrder = $this->getJson(['action' => 'listThreads']);
        $this->assertSame(200, $numberOrder['status']);
        $this->assertSame('Newer No with older date', $numberOrder['json'][0]['title']);
        $this->assertSame(2, $numberOrder['json'][0]['display_no']);

        getConnection()
            ->prepare('REPLACE INTO settings (key, value) VALUES (:key, :value)')
            ->execute([
                ':key' => 'config',
                ':value' => json_encode(['listOrder' => 'createdAt'], JSON_UNESCAPED_UNICODE),
            ]);

        $createdAtOrder = $this->getJson(['action' => 'listThreads']);
        $this->assertSame(200, $createdAtOrder['status']);
        $this->assertSame('Older No with newer date', $createdAtOrder['json'][0]['title']);
        $this->assertSame(1, $createdAtOrder['json'][0]['display_no']);
    }

    public function testRankingReturnsOnlyTopHundredForSelectedMetric(): void
    {
        $pdo = getConnection();
        $update = $pdo->prepare('UPDATE posts SET view_count = :views WHERE id = :id');
        for ($i = 1; $i <= 105; $i++) {
            $id = $this->insertPost('User', 'Rank ' . $i, 'Body', sprintf('2026-05-%02d 10:00:00', (($i - 1) % 28) + 1));
            $update->execute([':id' => $id, ':views' => $i]);
        }

        $response = $this->getJson(['action' => 'listRankingPosts', 'metric' => 'views']);
        $this->assertSame(200, $response['status']);
        $this->assertCount(100, $response['json']);
        $this->assertSame('Rank 105', $response['json'][0]['title']);
        $this->assertSame(105, $response['json'][0]['board_reactions']['views']);
        $this->assertSame('Rank 6', $response['json'][99]['title']);
        $this->assertSame(6, $response['json'][99]['board_reactions']['views']);
    }

    public function testDeletingThreadSoftDeletesRepliesWithoutPhysicalDeletion(): void
    {
        $this->postForm([
            'action' => 'createPost',
            'name' => 'Thread author',
            'title' => 'Thread title',
            'message' => 'Thread body',
            'password' => 'secret',
        ]);
        $threadId = (int)$this->latestPost()['id'];

        $this->postForm([
            'action' => 'createPost',
            'thread_id' => (string)$threadId,
            'parent_id' => (string)$threadId,
            'name' => 'Reply author',
            'title' => 'Re: Thread title',
            'message' => 'Reply body',
            'password' => 'reply-secret',
        ]);
        $replyId = (int)$this->latestPost()['id'];

        $deleted = $this->postForm([
            'action' => 'deletePost',
            'id' => (string)$threadId,
            'password' => 'secret',
        ]);

        $this->assertSame(200, $deleted['status']);
        $this->assertSame(2, $this->postCountIncludingDeleted());
        $this->assertNotNull($this->postById($threadId)['deleted_at']);
        $this->assertNotNull($this->postById($replyId)['deleted_at']);
    }

    public function testCreatePostStoresUploadedImageThroughHttp(): void
    {
        $image = $this->temporaryImage('upload-old-', 'old-png');

        $created = $this->postForm([
            'action' => 'createPost',
            'name' => 'Image user',
            'title' => 'Image title',
            'message' => 'Image body',
            'password' => 'secret',
            'file' => curl_file_create($image, 'image/png', 'drawing.png'),
        ]);

        $this->assertSame(200, $created['status']);
        $this->assertTrue($created['json']['success']);

        $post = $this->latestPost();
        $this->assertSame(STORAGE_DIR . '/' . $post['id'] . '.png', $post['image_path']);
        $this->assertFileExists($post['image_path']);
        $this->assertSame('old-png', file_get_contents($post['image_path']));
    }

    public function testReplacingImageThroughHttpKeepsArchivedHistory(): void
    {
        $oldImage = $this->temporaryImage('replace-old-', 'old-image');
        $newImage = $this->temporaryImage('replace-new-', 'new-image');

        $this->postForm([
            'action' => 'createPost',
            'name' => 'Image user',
            'title' => 'Image title',
            'message' => 'Image body',
            'password' => 'secret',
            'file' => curl_file_create($oldImage, 'image/png', 'drawing.png'),
        ]);
        $post = $this->latestPost();
        $id = (int)$post['id'];

        $updated = $this->postForm([
            'action' => 'updatePost',
            'id' => (string)$id,
            'name' => 'Image user',
            'title' => 'Image title updated',
            'message' => 'Image body updated',
            'password' => 'secret',
            'file' => curl_file_create($newImage, 'image/png', 'drawing.png'),
        ]);

        $this->assertSame(200, $updated['status']);
        $this->assertTrue($updated['json']['success']);
        $this->assertSame('new-image', file_get_contents(STORAGE_DIR . '/' . $id . '.png'));

        $archives = glob(STORAGE_DIR . '/' . $id . '_*.png') ?: [];
        $this->assertCount(1, $archives);
        $this->assertSame('old-image', file_get_contents($archives[0]));

        $row = $this->postById($id);
        $this->assertSame(STORAGE_DIR . '/' . $id . '.png', $row['image_path']);
    }

    public function testBackupExportAndImportPreserveReferencedImageData(): void
    {
        $this->setAdminPassword('admin-secret');
        $externalImage = $this->temporaryImage('backup-image-', 'backup-image-body');
        $externalIcon = $this->temporaryImage('backup-icon-', 'backup-icon-body');
        $pdo = getConnection();
        $userStmt = $pdo->prepare(
            'INSERT INTO users (login_id, password_hash, display_name, post_password, home_url, icon_path, created_at, updated_at)
             VALUES (:login_id, :password_hash, :display_name, :post_password, :home_url, :icon_path, :created_at, :updated_at)'
        );
        $userStmt->execute([
            ':login_id' => 'backup-user',
            ':password_hash' => password_hash('login-secret', PASSWORD_DEFAULT),
            ':display_name' => 'Backup User',
            ':post_password' => 'post-secret',
            ':home_url' => 'https://example.com',
            ':icon_path' => $externalIcon,
            ':created_at' => '2026-05-10 11:00:00',
            ':updated_at' => '2026-05-10 11:30:00',
        ]);
        $userId = (int)$pdo->lastInsertId();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, password_hash, created_at, view_count, user_id)
             VALUES (0, 0, :name, :title, :message, :image_path, :password_hash, :created_at, :view_count, :user_id)'
        );
        $stmt->execute([
            ':name' => 'Backup user',
            ':title' => 'Backup image',
            ':message' => 'Backup body',
            ':image_path' => $externalImage,
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':created_at' => '2026-05-10 12:00:00',
            ':view_count' => 123,
            ':user_id' => $userId,
        ]);
        $postId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id')->execute([':id' => $postId]);
        $pdo->prepare('INSERT INTO user_post_claims (user_id, post_id, created_at) VALUES (:user_id, :post_id, :created_at)')
            ->execute([':user_id' => $userId, ':post_id' => $postId, ':created_at' => '2026-05-10 12:30:00']);
        $pdo->prepare('INSERT INTO access_counts (access_date, count) VALUES (:access_date, :count)')
            ->execute([':access_date' => '2026-05-10', ':count' => 77]);

        $exported = $this->getJson(['action' => 'exportBackup', 'admin_password' => 'admin-secret']);
        $this->assertSame(200, $exported['status']);
        $this->assertNull($exported['json']);
        $imageName = basename($externalImage);
        $iconName = basename($externalIcon);
        $backupFile = $this->temporaryImage('backup-zip-', (string)$exported['body']);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($backupFile) === true);
        $backupJson = $zip->getFromName('backup.json');
        $this->assertIsString($backupJson);
        $payload = json_decode($backupJson, true);
        $this->assertIsArray($payload);
        $this->assertSame(3, $payload['backup_version']);
        $this->assertSame('sqlite-zip', $payload['backup_format']);
        $this->assertNotFalse($zip->locateName('database.sqlite'));
        $this->assertSame('backup-image-body', $zip->getFromName('images/' . $imageName));
        $this->assertSame('backup-icon-body', $zip->getFromName('images/' . $iconName));
        $zip->close();

        @unlink($externalImage);
        @unlink($externalIcon);
        $pdo->exec('DELETE FROM posts');
        $pdo->exec('DELETE FROM users');
        $pdo->exec('DELETE FROM user_post_claims');
        $pdo->exec('DELETE FROM access_counts');

        $imported = $this->postForm([
            'action' => 'importBackup',
            'admin_password' => 'admin-secret',
            'backup' => curl_file_create($backupFile, 'application/zip', 'backup.zip'),
        ]);

        $this->assertSame(200, $imported['status']);
        $this->assertTrue($imported['json']['success']);
        $row = $this->postById($postId);
        $this->assertIsArray($row);
        $this->assertSame(STORAGE_DIR . '/' . $imageName, $row['image_path']);
        $this->assertSame(123, (int)$row['view_count']);
        $this->assertSame($userId, (int)$row['user_id']);
        $this->assertFileExists(STORAGE_DIR . '/' . $imageName);
        $this->assertSame('backup-image-body', file_get_contents(STORAGE_DIR . '/' . $imageName));
        $user = getConnection()->query('SELECT * FROM users WHERE id = ' . $userId)->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($user);
        $this->assertSame(STORAGE_DIR . '/' . $iconName, $user['icon_path']);
        $this->assertFileExists(STORAGE_DIR . '/' . $iconName);
        $this->assertSame('backup-icon-body', file_get_contents(STORAGE_DIR . '/' . $iconName));
        $this->assertSame(1, (int)getConnection()->query('SELECT COUNT(*) FROM user_post_claims WHERE user_id = ' . $userId . ' AND post_id = ' . $postId)->fetchColumn());
        $this->assertSame(77, (int)getConnection()->query("SELECT count FROM access_counts WHERE access_date = '2026-05-10'")->fetchColumn());
    }

    public function testSearchApiHonorsEmptyQueryLimitPageScopeAndEscapedWildcards(): void
    {
        $this->insertPost('Alice', 'Needle title', 'Body one', '2026-05-01 10:00:00');
        $this->insertPost('Bob', 'Second title', 'Needle body', '2026-05-01 11:00:00');
        $this->insertPost('Carol', 'Literal 100% match', 'No wildcard', '2026-05-01 12:00:00');
        $this->insertPost('Dave', 'Literal 1000 match', 'No wildcard', '2026-05-01 13:00:00');

        $empty = $this->getJson(['action' => 'search', 'q' => '']);
        $this->assertSame(200, $empty['status']);
        $this->assertSame([], $empty['json']);

        $limited = $this->getJson(['action' => 'search', 'q' => 'title', 'limit' => '1', 'page' => '2']);
        $this->assertSame(200, $limited['status']);
        $this->assertCount(1, $limited['json']);
        $this->assertSame('Needle title', $limited['json'][0]['title']);

        $messageScope = $this->getJson(['action' => 'search', 'q' => 'Needle', 'scope' => 'message']);
        $this->assertSame(200, $messageScope['status']);
        $this->assertCount(1, $messageScope['json']);
        $this->assertSame('Needle body', $messageScope['json'][0]['message']);

        $literalPercent = $this->getJson(['action' => 'search', 'q' => '100%']);
        $this->assertSame(200, $literalPercent['status']);
        $this->assertCount(1, $literalPercent['json']);
        $this->assertSame('Literal 100% match', $literalPercent['json'][0]['title']);
    }

    public function testPasswordMismatchAndMissingPostReturnErrors(): void
    {
        $this->postForm([
            'action' => 'createPost',
            'name' => 'Alice',
            'title' => 'Title',
            'message' => 'Body',
            'password' => 'secret',
        ]);
        $id = (int)$this->latestPost()['id'];

        $badUpdate = $this->postForm([
            'action' => 'updatePost',
            'id' => (string)$id,
            'name' => 'Alice',
            'title' => 'Changed',
            'message' => 'Changed',
            'password' => 'wrong',
        ]);
        $this->assertSame(403, $badUpdate['status']);
        $this->assertFalse($badUpdate['json']['success']);

        $badDelete = $this->postForm([
            'action' => 'deletePost',
            'id' => (string)$id,
            'password' => 'wrong',
        ]);
        $this->assertSame(403, $badDelete['status']);
        $this->assertFalse($badDelete['json']['success']);

        $missing = $this->postForm([
            'action' => 'deletePost',
            'id' => '999999',
            'password' => 'secret',
        ]);
        $this->assertSame(404, $missing['status']);
        $this->assertFalse($missing['json']['success']);
    }

    public function testPresetReactionReplyCannotBeEditedThroughHttp(): void
    {
        $this->postForm([
            'action' => 'createPost',
            'name' => 'Thread author',
            'title' => 'Thread title',
            'message' => 'Thread body',
            'password' => 'secret',
        ]);
        $threadId = (int)$this->latestPost()['id'];

        $this->postForm([
            'action' => 'createPost',
            'thread_id' => (string)$threadId,
            'parent_id' => (string)$threadId,
            'name' => 'Blank',
            'title' => 'Re: Thread title',
            'message' => 'ええじゃないか',
            'password' => 'reply-secret',
        ]);
        $replyId = (int)$this->latestPost()['id'];

        $updated = $this->postForm([
            'action' => 'updatePost',
            'id' => (string)$replyId,
            'name' => 'Blank',
            'title' => 'Re: Thread title',
            'message' => 'Edited body',
            'password' => 'reply-secret',
        ]);

        $this->assertSame(403, $updated['status']);
        $this->assertFalse($updated['json']['success']);
        $this->assertSame('ええじゃないか', $this->postById($replyId)['message']);
    }

    public function testPresetReactionReplyUsesAdminPasswordForDeletion(): void
    {
        $this->setAdminPassword('admin-secret');

        $this->postForm([
            'action' => 'createPost',
            'name' => 'Thread author',
            'title' => 'Thread title',
            'message' => 'Thread body',
            'password' => 'secret',
        ]);
        $threadId = (int)$this->latestPost()['id'];

        $created = $this->postForm([
            'action' => 'createPost',
            'thread_id' => (string)$threadId,
            'parent_id' => (string)$threadId,
            'name' => 'Blank',
            'title' => 'Re: Thread title',
            'message' => 'ええじゃないか',
            'password' => 'eejanaika',
        ]);
        $this->assertSame(200, $created['status']);
        $replyId = (int)$this->latestPost()['id'];

        $oldFixedPassword = $this->postForm([
            'action' => 'deletePost',
            'id' => (string)$replyId,
            'password' => 'eejanaika',
        ]);
        $this->assertSame(403, $oldFixedPassword['status']);
        $this->assertFalse($oldFixedPassword['json']['success']);
        $this->assertNull($this->postById($replyId)['deleted_at']);

        $adminPassword = $this->postForm([
            'action' => 'deletePost',
            'id' => (string)$replyId,
            'password' => 'admin-secret',
        ]);
        $this->assertSame(200, $adminPassword['status']);
        $this->assertTrue($adminPassword['json']['success']);
        $this->assertNotNull($this->postById($replyId)['deleted_at']);
    }

    public function testUserDisplayNameIsLimitedToThirtyCharacters(): void
    {
        $tooLong = str_repeat('a', 31);
        $rejected = $this->postForm([
            'action' => 'registerUser',
            'login_id' => 'longname',
            'password' => 'password123',
            'display_name' => $tooLong,
            'post_password' => 'secret',
        ]);
        $this->assertSame(400, $rejected['status']);
        $this->assertFalse($rejected['json']['success']);

        $registered = $this->postForm([
            'action' => 'registerUser',
            'login_id' => 'validname',
            'password' => 'password123',
            'display_name' => str_repeat('b', 30),
            'post_password' => 'secret',
        ]);
        $this->assertSame(200, $registered['status']);
        $this->assertTrue($registered['json']['success']);

        $updated = $this->postForm([
            'action' => 'updateUserProfile',
            'auth_token' => $registered['json']['token'],
            'display_name' => $tooLong,
            'post_password' => 'secret',
        ]);
        $this->assertSame(400, $updated['status']);
        $this->assertFalse($updated['json']['success']);
    }

    public function testUpdatingUserIconUsesFreshFilePathToAvoidBrowserCache(): void
    {
        $firstIcon = $this->temporaryImage('user-icon-first-', 'first-icon');
        $secondIcon = $this->temporaryImage('user-icon-second-', 'second-icon');

        $registered = $this->postForm([
            'action' => 'registerUser',
            'login_id' => 'iconuser',
            'password' => 'password123',
            'display_name' => 'Icon User',
            'post_password' => 'secret',
            'icon' => curl_file_create($firstIcon, 'image/png', 'icon.png'),
        ]);
        $this->assertSame(200, $registered['status']);
        $firstPath = getConnection()
            ->query("SELECT icon_path FROM users WHERE login_id = 'iconuser'")
            ->fetchColumn();
        $this->assertIsString($firstPath);
        $this->assertFileExists($firstPath);
        $this->assertSame('first-icon', file_get_contents($firstPath));

        $updated = $this->postForm([
            'action' => 'updateUserProfile',
            'auth_token' => $registered['json']['token'],
            'display_name' => 'Icon User',
            'post_password' => 'secret',
            'icon' => curl_file_create($secondIcon, 'image/png', 'icon.png'),
        ]);
        $this->assertSame(200, $updated['status']);
        $secondPath = getConnection()
            ->query("SELECT icon_path FROM users WHERE login_id = 'iconuser'")
            ->fetchColumn();
        $this->assertIsString($secondPath);
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFileExists($secondPath);
        $this->assertSame('second-icon', file_get_contents($secondPath));
    }

    public function testListThreadsCanLoadPageContainingTargetPost(): void
    {
        $firstId = $this->insertPost('A', 'First', 'Body', '2026-05-01 10:00:00');
        $this->insertPost('B', 'Second', 'Body', '2026-05-02 10:00:00');
        $this->insertPost('C', 'Third', 'Body', '2026-05-03 10:00:00');

        $response = $this->getJson([
            'action' => 'listThreads',
            'limit' => '2',
            'target_id' => (string)$firstId,
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertContains($firstId, array_column($response['json'], 'id'));
    }

    public function testLegacyDefaultManualSettingsUseCurrentDefaults(): void
    {
        getConnection()
            ->prepare('REPLACE INTO settings (key, value) VALUES (:key, :value)')
            ->execute([
                ':key' => 'config',
                ':value' => json_encode([
                    'manualTitle' => 'ThreadForge 取扱説明書',
                    'manualBody' => implode("\n", [
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
                    ]),
                ], JSON_UNESCAPED_UNICODE),
            ]);

        $response = $this->getJson(['action' => 'publicSettings']);

        $this->assertSame(200, $response['status']);
        $this->assertSame('ThreadForge', $response['json']['settings']['config']['manualTitle']);
        $this->assertSame('特殊投稿', $response['json']['settings']['config']['gdgdLabel']);
        $this->assertStringContainsString('この取説は、このサイトを利用する方向けの案内です。', $response['json']['settings']['config']['manualBody']);
        $this->assertStringContainsString('# 【HOME】', $response['json']['settings']['config']['manualBody']);
        $this->assertStringContainsString('# 【ユーザー設定】', $response['json']['settings']['config']['manualBody']);
    }

    public function testListUserPostsReturnsOwnedAndClaimedParentPostsOnly(): void
    {
        $userId = $this->insertUser('owner', 'Owner');
        $otherUserId = $this->insertUser('other', 'Other');
        $ownedId = $this->insertPost('Owner', 'Owned title', 'Owned body', '2026-05-01 10:00:00', $userId);
        $claimedId = $this->insertPost('Other', 'Claimed title', 'Claimed body', '2026-05-01 11:00:00', $otherUserId);
        $this->insertReply($ownedId, 'Owner reply', '2026-05-01 12:00:00', $userId);

        getConnection()->prepare('INSERT INTO user_post_claims (user_id, post_id, created_at) VALUES (:user_id, :post_id, :created_at)')
            ->execute([':user_id' => $userId, ':post_id' => $claimedId, ':created_at' => '2026-05-01 12:30:00']);

        $response = $this->getJson(['action' => 'listUserPosts', 'user_id' => (string)$userId]);

        $this->assertSame(200, $response['status']);
        $this->assertTrue($response['json']['success']);
        $this->assertSame('Owner', $response['json']['user']['display_name']);
        $this->assertEqualsCanonicalizing([$ownedId, $claimedId], array_column($response['json']['posts'], 'id'));
        $this->assertNotContains($this->latestPost()['id'], array_column($response['json']['posts'], 'id'));
    }

    public function testClaimUserPostRegistersDisplayNoAsOwnWork(): void
    {
        $userId = $this->insertUser('claimant', 'Claimant');
        $postId = $this->insertPost('Other', 'Claim target', 'Body', '2026-05-01 10:00:00');
        $token = createUserSession(getConnection(), $userId);

        $response = $this->postForm([
            'action' => 'claimUserPost',
            'auth_token' => $token,
            'id' => '1',
        ]);

        $this->assertSame(200, $response['status'], (string)$response['body']);
        $this->assertIsArray($response['json'], (string)$response['body']);
        $this->assertTrue($response['json']['success']);
        $this->assertSame(
            1,
            (int)getConnection()
                ->query('SELECT COUNT(*) FROM user_post_claims WHERE user_id = ' . $userId . ' AND post_id = ' . $postId)
                ->fetchColumn()
        );
    }

    public function testClaimUserPostUsesDisplayedThreadNumberNotInternalId(): void
    {
        $userId = $this->insertUser('claimant', 'Claimant');
        $firstId = $this->insertPost('First', 'First', 'Body', '2026-05-01 10:00:00');
        $replyId = $this->insertReply($firstId, 'Reply body', '2026-05-01 10:30:00');
        $secondId = $this->insertPost('Second', 'Second', 'Body', '2026-05-01 11:00:00');
        $token = createUserSession(getConnection(), $userId);

        $response = $this->postForm([
            'action' => 'claimUserPost',
            'auth_token' => $token,
            'id' => '2',
        ]);

        $this->assertSame(200, $response['status'], (string)$response['body']);
        $this->assertTrue($response['json']['success']);
        $this->assertSame(
            1,
            (int)getConnection()
                ->query('SELECT COUNT(*) FROM user_post_claims WHERE user_id = ' . $userId . ' AND post_id = ' . $secondId)
                ->fetchColumn()
        );
        $this->assertSame(
            0,
            (int)getConnection()
                ->query('SELECT COUNT(*) FROM user_post_claims WHERE user_id = ' . $userId . ' AND post_id = ' . $replyId)
                ->fetchColumn()
        );
    }

    public function testUnclaimUserPostRemovesOwnWorkRegistration(): void
    {
        $userId = $this->insertUser('claimant', 'Claimant');
        $postId = $this->insertPost('Other', 'Claim target', 'Body', '2026-05-01 10:00:00');
        getConnection()->prepare('INSERT INTO user_post_claims (user_id, post_id, created_at) VALUES (:user_id, :post_id, :created_at)')
            ->execute([':user_id' => $userId, ':post_id' => $postId, ':created_at' => '2026-05-01 11:00:00']);
        $token = createUserSession(getConnection(), $userId);

        $response = $this->postForm([
            'action' => 'unclaimUserPost',
            'auth_token' => $token,
            'id' => '1',
        ]);

        $this->assertSame(200, $response['status'], (string)$response['body']);
        $this->assertTrue($response['json']['success']);
        $this->assertSame(
            0,
            (int)getConnection()
                ->query('SELECT COUNT(*) FROM user_post_claims WHERE user_id = ' . $userId . ' AND post_id = ' . $postId)
                ->fetchColumn()
        );
    }

    public function testAdminCanPurgeDeletedPostDataAndThenDestroyDisplayNumber(): void
    {
        $this->setAdminPassword('admin-secret');
        $firstId = $this->insertPost('First', 'First title', 'First body', '2026-05-01 10:00:00');
        $replyId = $this->insertReply($firstId, 'Reply body', '2026-05-01 10:30:00');
        $secondId = $this->insertPost('Second', 'Second title', 'Second body', '2026-05-02 10:00:00');

        $softDeleted = $this->postForm([
            'action' => 'adminDeletePosts',
            'ids' => (string)$firstId,
            'admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $softDeleted['status'], (string)$softDeleted['body']);
        $this->assertTrue($softDeleted['json']['success']);

        $purged = $this->postForm([
            'action' => 'purgePostData',
            'id' => (string)$firstId,
            'admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $purged['status'], (string)$purged['body']);
        $this->assertTrue($purged['json']['success']);
        $this->assertSame('消去済み', $this->postById($firstId)['title']);
        $this->assertNull($this->postById($replyId));

        $beforeDestroy = $this->getJson(['action' => 'listThreads']);
        $this->assertSame(200, $beforeDestroy['status']);
        $this->assertSame($secondId, $beforeDestroy['json'][0]['id']);
        $this->assertSame(2, $beforeDestroy['json'][0]['display_no']);

        $destroyed = $this->postForm([
            'action' => 'destroyPostNumber',
            'id' => (string)$firstId,
            'admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $destroyed['status'], (string)$destroyed['body']);
        $this->assertTrue($destroyed['json']['success']);
        $this->assertNull($this->postById($firstId));

        $afterDestroy = $this->getJson(['action' => 'listThreads']);
        $this->assertSame(200, $afterDestroy['status']);
        $this->assertSame($secondId, $afterDestroy['json'][0]['id']);
        $this->assertSame(1, $afterDestroy['json'][0]['display_no']);
    }

    public function testAdminCanEditEraseAndCompactUsers(): void
    {
        $this->setAdminPassword('admin-secret');
        $firstUserId = $this->insertUser('first', 'First');
        $secondUserId = $this->insertUser('second', 'Second');
        $login = $this->postForm([
            'action' => 'loginUser',
            'login_id' => 'first',
            'password' => 'secret',
        ]);
        $this->assertSame(200, $login['status'], (string)$login['body']);
        $this->assertTrue($login['json']['success']);

        $updated = $this->postForm([
            'action' => 'adminUpdateUser',
            'admin_password' => 'admin-secret',
            'id' => (string)$firstUserId,
            'login_id' => 'first2',
            'display_name' => 'First Updated',
            'post_password' => 'poster',
            'home_url' => 'https://example.com/',
        ]);
        $this->assertSame(200, $updated['status'], (string)$updated['body']);
        $this->assertTrue($updated['json']['success']);

        $users = $this->getJson(['action' => 'listAdminUsers', 'admin_password' => 'admin-secret']);
        $this->assertSame(200, $users['status']);
        $this->assertSame('first2', $users['json']['users'][0]['login_id']);
        $this->assertSame('First Updated', $users['json']['users'][0]['display_name']);
        $this->assertNotEmpty($users['json']['users'][0]['last_login_at']);
        $this->assertSame(1, $users['json']['users'][0]['active_session_count']);

        $erased = $this->postForm([
            'action' => 'adminDeleteUser',
            'admin_password' => 'admin-secret',
            'id' => (string)$firstUserId,
            'stage' => '1',
        ]);
        $this->assertSame(200, $erased['status'], (string)$erased['body']);
        $this->assertTrue($erased['json']['success']);

        $erasedUsers = $this->getJson(['action' => 'listAdminUsers', 'admin_password' => 'admin-secret']);
        $this->assertSame('deleted_user_' . $firstUserId, $erasedUsers['json']['users'][0]['login_id']);
        $this->assertSame('削除済みユーザー', $erasedUsers['json']['users'][0]['display_name']);
        $this->assertSame($firstUserId, $erasedUsers['json']['users'][0]['id']);

        $destroyed = $this->postForm([
            'action' => 'adminDeleteUser',
            'admin_password' => 'admin-secret',
            'id' => (string)$firstUserId,
            'stage' => '2',
        ]);
        $this->assertSame(200, $destroyed['status'], (string)$destroyed['body']);
        $this->assertTrue($destroyed['json']['success']);

        $compactedUsers = $this->getJson(['action' => 'listAdminUsers', 'admin_password' => 'admin-secret']);
        $this->assertCount(1, $compactedUsers['json']['users']);
        $this->assertSame(1, $compactedUsers['json']['users'][0]['id']);
        $this->assertSame('second', $compactedUsers['json']['users'][0]['login_id']);
        $this->assertNotSame($secondUserId, $compactedUsers['json']['users'][0]['id']);
    }

    private function startServer(): void
    {
        $port = $this->findFreePort();
        $this->baseUrl = 'http://127.0.0.1:' . $port . '/api.php';
        $serverRoot = dirname(__DIR__);
        $bootstrap = __DIR__ . '/bootstrap.php';
        $command = sprintf(
            '"%s" -d auto_prepend_file="%s" -S 127.0.0.1:%d -t "%s"',
            PHP_BINARY,
            $bootstrap,
            $port,
            $serverRoot
        );
        $env = array_merge(getenv(), [
            'THREADFORGE_DB_FILE' => DB_FILE,
            'THREADFORGE_STORAGE_DIR' => STORAGE_DIR,
        ]);

        $this->serverProcess = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $serverRoot,
            $env
        );

        if (!is_resource($this->serverProcess)) {
            $this->fail('Failed to start PHP built-in server.');
        }
        $status = proc_get_status($this->serverProcess);
        $this->serverProcessId = (int)($status['pid'] ?? 0);

        $deadline = microtime(true) + 5.0;
        do {
            try {
                $response = $this->getJson(['action' => 'version']);
                if ($response['status'] === 200) {
                    return;
                }
            } catch (RuntimeException $exception) {
                usleep(100000);
            }
        } while (microtime(true) < $deadline);

        $this->fail('PHP built-in server did not become ready.');
    }

    private function stopServer(): void
    {
        if ($this->serverProcessId > 0 && PHP_OS_FAMILY === 'Windows') {
            exec('taskkill /F /T /PID ' . $this->serverProcessId . ' >NUL 2>NUL');
        }
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
            $this->serverProcess = null;
        }
        $this->serverProcessId = 0;
    }

    private function getJson(array $query): array
    {
        return $this->request('GET', $this->baseUrl . '?' . http_build_query($query), []);
    }

    private function postForm(array $fields): array
    {
        return $this->request('POST', $this->baseUrl, $fields);
    }

    private function request(string $method, string $url, array $fields): array
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $fields);
        }

        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException($message);
        }

        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $json = json_decode((string)$body, true);

        return [
            'status' => $status,
            'body' => $body,
            'json' => $json,
        ];
    }

    private function latestPost(): array
    {
        $row = getConnection()
            ->query('SELECT * FROM posts ORDER BY id DESC LIMIT 1')
            ->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        return $row;
    }

    private function postById(int $id): ?array
    {
        $stmt = getConnection()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function postCountIncludingDeleted(): int
    {
        return (int)getConnection()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    private function insertUser(string $loginId, string $displayName): int
    {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO users (login_id, password_hash, display_name, post_password, home_url, created_at, updated_at)
             VALUES (:login_id, :password_hash, :display_name, :post_password, null, :created_at, :updated_at)'
        );
        $stmt->execute([
            ':login_id' => $loginId,
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':display_name' => $displayName,
            ':post_password' => 'secret',
            ':created_at' => '2026-05-01 09:00:00',
            ':updated_at' => '2026-05-01 09:00:00',
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function insertPost(string $name, string $title, string $message, string $createdAt, ?int $userId = null): int
    {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, password_hash, created_at, user_id)
             VALUES (0, 0, :name, :title, :message, null, :password_hash, :created_at, :user_id)'
        );
        $stmt->execute([
            ':name' => $name,
            ':title' => $title,
            ':message' => $message,
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':created_at' => $createdAt,
            ':user_id' => $userId,
        ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id')->execute([':id' => $id]);
        return $id;
    }

    private function insertReply(int $threadId, string $message, string $createdAt, ?int $userId = null): int
    {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, password_hash, created_at, user_id)
             VALUES (:thread_id, :parent_id, :name, :title, :message, null, :password_hash, :created_at, :user_id)'
        );
        $stmt->execute([
            ':thread_id' => $threadId,
            ':parent_id' => $threadId,
            ':name' => 'Reply',
            ':title' => 'Re: Title',
            ':message' => $message,
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':created_at' => $createdAt,
            ':user_id' => $userId,
        ]);
        return (int)$pdo->lastInsertId();
    }

    private function setAdminPassword(string $password): void
    {
        getConnection()
            ->prepare('REPLACE INTO settings (key, value) VALUES (:key, :value)')
            ->execute([
                ':key' => 'security',
                ':value' => json_encode(['adminPasswordHash' => password_hash($password, PASSWORD_DEFAULT)], JSON_UNESCAPED_UNICODE),
            ]);
    }

    private function temporaryImage(string $prefix, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($path, $contents);

        return $path;
    }

    private function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        if ($socket === false) {
            $this->fail('Failed to allocate a local TCP port.');
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $parts = explode(':', (string)$name);

        return (int)end($parts);
    }

    private function resetRuntime(): void
    {
        $expectedRuntime = __DIR__ . '/runtime';
        $runtimeRoot = dirname(DB_FILE);
        $this->assertSame(realpath(__DIR__) . DIRECTORY_SEPARATOR . 'runtime', $this->normalizePath($runtimeRoot));

        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        if (is_dir($runtimeRoot)) {
            $this->removeDirectory($runtimeRoot);
        }

        mkdir($expectedRuntime, 0775, true);
    }

    private function normalizePath(string $path): string
    {
        $resolved = realpath($path);
        if ($resolved !== false) {
            return $resolved;
        }

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir((string)$item);
            } else {
                unlink((string)$item);
            }
        }

        rmdir($directory);
    }
}
