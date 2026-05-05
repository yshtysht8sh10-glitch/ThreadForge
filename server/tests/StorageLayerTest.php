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
        $this->assertSame(4, $post['tweet_impression_count']);
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
