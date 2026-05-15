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

    private function insertPost(string $name, string $title, string $message, string $createdAt): void
    {
        $pdo = getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO posts (thread_id, parent_id, name, title, message, image_path, password_hash, created_at)
             VALUES (0, 0, :name, :title, :message, null, :password_hash, :created_at)'
        );
        $stmt->execute([
            ':name' => $name,
            ':title' => $title,
            ':message' => $message,
            ':password_hash' => password_hash('secret', PASSWORD_DEFAULT),
            ':created_at' => $createdAt,
        ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE posts SET thread_id = :id WHERE id = :id')->execute([':id' => $id]);
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
