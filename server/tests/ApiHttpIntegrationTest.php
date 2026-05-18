<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiHttpIntegrationTest extends TestCase
{
    private $serverProcess = null;
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

    public function testDefaultAdminPasswordCanOpenSettingsWhenNoPasswordIsConfigured(): void
    {
        $denied = $this->getJson(['action' => 'getSettings', 'admin_password' => 'wrong']);
        $this->assertSame(403, $denied['status']);
        $this->assertFalse($denied['json']['success']);

        $allowed = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin']);
        $this->assertSame(200, $allowed['status']);
        $this->assertTrue($allowed['json']['success']);
        $this->assertArrayHasKey('settings', $allowed['json']);
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
        $this->assertStringContainsString('# 【ユーザーページ】', $response['json']['settings']['config']['manualBody']);
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

    private function startServer(): void
    {
        $port = $this->findFreePort();
        $this->baseUrl = 'http://127.0.0.1:' . $port . '/api.php';
        $serverRoot = dirname(__DIR__);
        $command = sprintf(
            '"%s" -S 127.0.0.1:%d -t "%s"',
            PHP_BINARY,
            $port,
            $serverRoot
        );

        $this->serverProcess = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $serverRoot
        );

        if (!is_resource($this->serverProcess)) {
            $this->fail('Failed to start PHP built-in server.');
        }

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
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
            $this->serverProcess = null;
        }
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
