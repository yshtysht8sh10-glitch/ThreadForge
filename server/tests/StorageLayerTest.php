<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StorageLayerTest extends TestCase
{
    protected function setUp(): void
    {
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        if (is_dir(STORAGE_DIR)) {
            $this->removeDirectory(STORAGE_DIR);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists(DB_FILE)) {
            unlink(DB_FILE);
        }

        if (is_dir(STORAGE_DIR)) {
            $this->removeDirectory(STORAGE_DIR);
        }
    }

    // #sym:describe getConnection
    public function testGetConnectionInitializesDatabaseAndStorage(): void
    {
        $pdo = getConnection();

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertFileExists(DB_FILE);
        $this->assertDirectoryExists(STORAGE_DIR);

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='posts'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($result);
        $this->assertSame('posts', $result['name']);

        $columns = $pdo->query('PRAGMA table_info(posts)')->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        $this->assertContains('deleted_at', $columnNames);
        $this->assertContains('url', $columnNames);
        $this->assertContains('gdgd', $columnNames);
        $this->assertContains('tweet_off', $columnNames);
        $this->assertContains('tweet_text', $columnNames);
        $this->assertContains('tweet_url', $columnNames);
        $this->assertContains('tweet_like_count', $columnNames);
        $this->assertContains('tweet_retweet_count', $columnNames);
        $this->assertContains('tweet_comment_count', $columnNames);
        $this->assertContains('tweet_impression_count', $columnNames);
    }

    // #sym:describe buildPost
    public function testBuildPostConvertsImagePathToPublicUrl(): void
    {
        $row = [
            'id' => 123,
            'thread_id' => 123,
            'parent_id' => 0,
            'name' => 'User',
            'url' => 'https://example.com',
            'title' => 'Title',
            'message' => 'Message',
            'image_path' => STORAGE_DIR . '/example.png',
            'created_at' => '2026-05-03 12:00:00',
            'deleted_at' => null,
            'gdgd' => 1,
            'tweet_off' => 0,
            'tweet_text' => 'tweet',
            'tweet_url' => null,
            'tweet_like_count' => 1,
            'tweet_retweet_count' => 2,
            'tweet_comment_count' => 3,
            'tweet_impression_count' => 4,
        ];

        $post = buildPost($row);

        $this->assertSame(123, $post['id']);
        $this->assertSame('/storage/data/example.png', $post['image_path']);
        $this->assertSame('https://example.com', $post['url']);
        $this->assertTrue($post['gdgd']);
        $this->assertFalse($post['tweet_off']);
        $this->assertArrayNotHasKey('tweet_impression_count', $post);
    }

    // #sym:describe normalizeString
    public function testNormalizeStringTrimsWhitespace(): void
    {
        $this->assertSame('abc', normalizeString("  abc  \n\t"));
    }

    // #sym:describe allowsImageUpload
    public function testAllowsImageUploadOnlyForNewTopLevelPosts(): void
    {
        $this->assertTrue(allowsImageUpload(0, 0));
        $this->assertFalse(allowsImageUpload(1, 0));
        $this->assertFalse(allowsImageUpload(1, 1));
    }

    // #sym:describe resolveAction
    public function testResolveActionAcceptsPostBodyAction(): void
    {
        $this->assertSame('createPost', resolveAction([], ['action' => 'createPost']));
        $this->assertSame('listThreads', resolveAction(['action' => 'listThreads'], ['action' => 'createPost']));
        $this->assertNull(resolveAction([], []));
    }

    // #sym:describe findPostById
    public function testFindPostByIdReturnsInsertedRow(): void
    {
        $pdo = getConnection();

        $pdo->exec("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at) VALUES (1, 0, 'Test', 'Hello', 'Body', 'storage/data/test.png', '2026-05-03 12:00:00')");
        $id = (int)$pdo->lastInsertId();

        $result = findPostById($pdo, $id);

        $this->assertIsArray($result);
        $this->assertSame($id, (int)$result['id']);
        $this->assertSame('Test', $result['name']);
        $this->assertSame('storage/data/test.png', $result['image_path']);
    }

    // #sym:describe deleteImage
    public function testDeleteImageRemovesFile(): void
    {
        if (!is_dir(STORAGE_DIR)) {
            mkdir(STORAGE_DIR, 0775, true);
        }

        $path = STORAGE_DIR . '/delete-test.png';
        file_put_contents($path, 'test');
        $this->assertFileExists($path);

        deleteImage($path);
        $this->assertFileDoesNotExist($path);
    }

    public function testDeleteImageDoesNotThrowWhenFileMissing(): void
    {
        $path = STORAGE_DIR . '/missing.png';
        deleteImage($path);
        $this->assertFileDoesNotExist($path);
    }

    // #sym:describe saveUploadedImage
    public function testSaveUploadedImageReturnsNullForNonUploadedFile(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'phpunit-');
        file_put_contents($tmp, 'dummy');

        $file = [
            'tmp_name' => $tmp,
            'name' => 'dummy.png',
            'type' => 'image/png',
            'error' => UPLOAD_ERR_OK,
        ];

        $result = saveUploadedImage($file, 123);
        $this->assertNull($result);

        unlink($tmp);
    }

    // #sym:describe findActivePostById
    public function testFindActivePostByIdExcludesSoftDeletedRows(): void
    {
        $pdo = getConnection();

        $pdo->exec("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at, deleted_at) VALUES (1, 0, 'Test', 'Hello', 'Body', null, '2026-05-03 12:00:00', '2026-05-04 12:00:00')");
        $id = (int)$pdo->lastInsertId();

        $this->assertIsArray(findPostById($pdo, $id));
        $this->assertNull(findActivePostById($pdo, $id));
    }

    // #sym:describe imageExtensionFromMime
    public function testImageExtensionFromMimeReturnsStorageExtension(): void
    {
        $this->assertSame('png', imageExtensionFromMime('image/png'));
        $this->assertSame('jpg', imageExtensionFromMime('image/jpeg'));
        $this->assertSame('gif', imageExtensionFromMime('image/gif'));
        $this->assertNull(imageExtensionFromMime('text/plain'));
    }

    // #sym:describe archiveExistingImage
    public function testArchiveExistingImageKeepsOldImageWithHistoryName(): void
    {
        if (!is_dir(STORAGE_DIR)) {
            mkdir(STORAGE_DIR, 0775, true);
        }

        $path = STORAGE_DIR . '/123.png';
        file_put_contents($path, 'old-image');

        $archivePath = archiveExistingImage($path);

        $this->assertIsString($archivePath);
        $this->assertFileDoesNotExist($path);
        $this->assertFileExists($archivePath);
        $this->assertSame('old-image', file_get_contents($archivePath));
        $this->assertMatchesRegularExpression('/123_\d{14}\.png$/', $archivePath);
    }

    // #sym:describe normalizeUrl
    public function testNormalizeUrlAcceptsBareDomainsAndRejectsInvalidValues(): void
    {
        $this->assertSame('https://example.com', normalizeUrl('example.com'));
        $this->assertSame('http://example.com/a', normalizeUrl('http://example.com/a'));
        $this->assertNull(normalizeUrl('http://'));
        $this->assertNull(normalizeUrl('not a url'));
    }

    // #sym:describe buildTweetText
    public function testBuildTweetTextUsesTwendMarkerAndFitsLimit(): void
    {
        $tweet = buildTweetText('Alice', 'Title', str_repeat('あ', 400) . '_TWEND_ hidden', 'https://example.com/thread/1');

        $this->assertStringContainsString('[DT000000：Title]', $tweet);
        $this->assertStringContainsString('作者：Alice', $tweet);
        $this->assertStringNotContainsString('hidden', $tweet);
        $this->assertLessThanOrEqual(280, countTweetLength($tweet));
    }

    // #sym:describe hasRecentDuplicatePost
    public function testHasRecentDuplicatePostDetectsActiveDuplicates(): void
    {
        $pdo = getConnection();
        $now = currentTimestamp();
        $pdo->prepare("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at) VALUES (1, 0, 'Alice', 'Same', 'Body', null, :created_at)")
            ->execute([':created_at' => $now]);

        $this->assertTrue(hasRecentDuplicatePost($pdo, 'Alice', 'Same', 'Body', 60));
        $this->assertFalse(hasRecentDuplicatePost($pdo, 'Alice', 'Other', 'Body', 60));
    }

    // #sym:describe buildDeletedPost
    public function testBuildDeletedPostUsesThreadDisplayAndReplyNumbers(): void
    {
        $pdo = getConnection();
        $pdo->exec("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at, deleted_at) VALUES (0, 0, 'Alice', 'First', 'Body', null, '2026-05-01 12:00:00', null)");
        $firstId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id')->execute([':id' => $firstId]);

        $pdo->exec("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at, deleted_at) VALUES (0, 0, 'Bob', 'Second', 'Body', null, '2026-05-01 13:00:00', '2026-05-02 12:00:00')");
        $secondId = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id')->execute([':id' => $secondId]);

        $pdo->prepare("INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, created_at, deleted_at) VALUES (:thread_id, :parent_id, 'Carol', 'Re: Second', 'Reply', null, '2026-05-01 13:10:00', '2026-05-02 12:00:00')")
            ->execute([':thread_id' => $secondId, ':parent_id' => $secondId]);
        $replyId = (int)$pdo->lastInsertId();

        $thread = buildDeletedPost($pdo, findPostById($pdo, $secondId));
        $reply = buildDeletedPost($pdo, findPostById($pdo, $replyId));

        $this->assertSame(2, $thread['display_no']);
        $this->assertSame(2, $reply['display_no']);
        $this->assertSame(1, $reply['reply_no']);
    }

    // #sym:describe importLegacyBbsnoteDirectory
    public function testImportLegacyBbsnoteDirectoryAddsThreadsRepliesAndImagesWithoutDuplicates(): void
    {
        $pdo = getConnection();
        $legacyDir = sys_get_temp_dir() . '/threadforge-legacy-' . bin2hex(random_bytes(4));
        mkdir($legacyDir, 0775, true);

        $main = array_pad([
            '20403',
            'Legacy User',
            '2022/01/16 (Sun) 20:53:30',
            'Legacy Title',
            '',
            'https://example.com/home',
            'Line1<BR><SPAN class="quot">&gt;quoted</SPAN>',
            'host',
            '127.0.0.1',
            'agent',
            'DOTIMG_009359_1.gif',
            '254',
            '306',
            '321881',
            'legacy-password',
            '',
            '',
            '',
            '',
            'https://x.com/example/status/1',
        ], 24, '');
        $reply = array_pad([
            '20405',
            'Reply User',
            '2022/01/17 (Mon) 01:02:03',
            'Reply<BR>Body',
            'reply-password',
            'host',
            '',
            '',
            'browser',
            'os',
        ], 10, '');

        file_put_contents($legacyDir . '/LOG_009359.cgi', implode("\t", $main) . PHP_EOL . implode("\t", $reply) . PHP_EOL);
        file_put_contents($legacyDir . '/DOTIMG_009359_1.gif', 'gif-data');

        try {
            $first = importLegacyBbsnoteDirectory($pdo, $legacyDir);
            $second = importLegacyBbsnoteDirectory($pdo, $legacyDir);
        } finally {
            $this->removeDirectory($legacyDir);
        }

        $this->assertSame(1, $first['imported_threads']);
        $this->assertSame(1, $first['imported_replies']);
        $this->assertSame(0, $first['skipped_threads']);
        $this->assertSame(1, $second['skipped_threads']);
        $this->assertSame(1, $second['skipped_replies']);

        $rows = $pdo->query('SELECT * FROM posts ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertSame('Legacy Title', $rows[0]['title']);
        $this->assertSame("Line1\n>quoted", $rows[0]['message']);
        $this->assertSame('https://x.com/example/status/1', $rows[0]['tweet_url']);
        $this->assertFileExists((string)$rows[0]['image_path']);
        $this->assertSame((int)$rows[0]['id'], (int)$rows[1]['thread_id']);
        $this->assertSame("Reply\nBody", $rows[1]['message']);
    }

    private function removeDirectory(string $directory): void
    {
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
