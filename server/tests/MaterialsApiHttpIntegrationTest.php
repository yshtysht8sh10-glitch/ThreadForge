<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MaterialsApiHttpIntegrationTest extends TestCase
{
    private $serverProcess = null;
    private int $serverProcessId = 0;
    private string $baseUrl = '';
    private string $runtimeDir;

    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The curl extension is required.');
        }
        $this->runtimeDir = __DIR__ . '/materials-runtime';
        $this->removeDirectory($this->runtimeDir);
        mkdir($this->runtimeDir, 0775, true);
        $this->startServer();
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        $this->removeDirectory($this->runtimeDir);
    }

    public function testGuestAndUserMaterialsStaySeparateAndAdminCanRestore(): void
    {
        $settings = $this->getJson(['action' => 'materialsSettings']);
        $this->assertSame(200, $settings['status'], $settings['body']);
        $this->assertSame('■素材庫■', $settings['json']['settings']['title']);
        $this->assertNotEmpty($settings['json']['tags']);
        $this->assertNotEmpty($settings['json']['terms']);
        $tagId = (string)$settings['json']['tags'][0]['id'];
        $termId = (string)$settings['json']['terms'][0]['id'];

        $guestId = $this->uploadMaterial($tagId, $termId, 'Same Author', '');

        $mismatchedRegistration = $this->postForm([
            'action' => 'registerUser',
            'login_id' => 'mismatched-user',
            'password' => 'login-secret',
            'password_confirm' => 'different-secret',
        ]);
        $this->assertSame(400, $mismatchedRegistration['status'], $mismatchedRegistration['body']);
        $this->assertStringContainsString('一致しません', $mismatchedRegistration['json']['message']);

        $registered = $this->postForm([
            'action' => 'registerUser',
            'login_id' => 'material-user',
            'password' => 'login-secret',
            'password_confirm' => 'login-secret',
        ]);
        $this->assertSame(200, $registered['status'], $registered['body']);
        $this->assertSame('material-user', $registered['json']['user']['login_id']);
        $token = (string)$registered['json']['token'];

        $profile = $this->postForm([
            'action' => 'updateMaterialProfile',
            'auth_token' => $token,
            'author_name' => 'Same Author',
            'post_password' => 'postkey',
            'default_terms' => json_encode([$termId => true]),
        ]);
        $this->assertSame(200, $profile['status'], $profile['body']);
        $this->assertSame('postkey', $profile['json']['user']['post_password']);
        $this->assertSame('Same Author', $profile['json']['user']['materials_author_name']);

        $login = $this->postForm([
            'action' => 'loginUser',
            'login_id' => 'material-user',
            'password' => 'login-secret',
        ]);
        $this->assertSame(200, $login['status'], $login['body']);

        $userId = $this->uploadMaterial($tagId, $termId, 'Same Author', $token);
        $listed = $this->getJson(['action' => 'listMaterialItems']);
        $this->assertSame(200, $listed['status'], $listed['body']);
        $this->assertCount(2, $listed['json']['items']);
        $this->assertCount(1, $listed['json']['items'][0]['media']);
        $this->assertSame('sample.mp3', $listed['json']['items'][0]['media'][0]['originalName']);
        $keys = array_column($listed['json']['items'], 'authorKey');
        $this->assertContains('guest:Same Author', $keys);
        $this->assertContains('user:' . $registered['json']['user']['id'], $keys);

        $deleted = $this->postForm([
            'action' => 'deleteMaterialItem',
            'id' => (string)$guestId,
            'password' => 'postkey',
        ]);
        $this->assertSame(200, $deleted['status'], $deleted['body']);
        $this->assertCount(1, $this->getJson(['action' => 'listMaterialItems'])['json']['items']);

        $initialized = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $initialized['status'], $initialized['body']);

        $deletedItems = $this->getJson([
            'action' => 'listDeletedMaterialItems',
            'admin_password' => 'admin-secret',
        ]);
        $this->assertCount(1, $deletedItems['json']['items']);

        $restored = $this->postForm([
            'action' => 'restoreMaterialItems',
            'admin_password' => 'admin-secret',
            'ids' => (string)$guestId,
        ]);
        $this->assertSame(200, $restored['status'], $restored['body']);
        $this->assertCount(2, $this->getJson(['action' => 'listMaterialItems'])['json']['items']);

        $adminDeleted = $this->postForm([
            'action' => 'adminDeleteMaterialItems',
            'admin_password' => 'admin-secret',
            'ids' => (string)$userId,
        ]);
        $this->assertSame(200, $adminDeleted['status'], $adminDeleted['body']);

        $purged = $this->postForm([
            'action' => 'purgeMaterialItems',
            'admin_password' => 'admin-secret',
            'ids' => (string)$userId,
        ]);
        $this->assertSame(200, $purged['status'], $purged['body']);
        $this->assertCount(1, $this->getJson(['action' => 'listMaterialItems'])['json']['items']);
    }

    public function testMaterialWithoutPostPasswordCanOnlyBeChangedByAdmin(): void
    {
        $settings = $this->getJson(['action' => 'materialsSettings']);
        $tagId = (string)$settings['json']['tags'][0]['id'];
        $termId = (string)$settings['json']['terms'][0]['id'];
        $itemId = $this->uploadMaterial($tagId, $termId, 'Admin Only Author', '');

        $pdo = new PDO('sqlite:' . $this->runtimeDir . '/database.sqlite');
        $pdo->prepare('UPDATE material_items SET password_hash = "" WHERE id = :id')
            ->execute([':id' => $itemId]);

        $listed = $this->getJson(['action' => 'listMaterialItems']);
        $item = array_values(array_filter(
            $listed['json']['items'],
            static fn(array $candidate): bool => (int)$candidate['id'] === $itemId
        ))[0];
        $this->assertTrue($item['adminOnly']);

        $deleted = $this->postForm([
            'action' => 'deleteMaterialItem',
            'id' => (string)$itemId,
            'password' => '',
        ]);
        $this->assertSame(403, $deleted['status'], $deleted['body']);
        $this->assertStringContainsString('管理画面からのみ変更', $deleted['json']['message']);

        $updated = $this->postForm([
            'action' => 'updateMaterialItem',
            'id' => (string)$itemId,
            'password' => '',
        ]);
        $this->assertSame(403, $updated['status'], $updated['body']);
        $this->assertStringContainsString('管理画面からのみ変更', $updated['json']['message']);

        $initialized = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $initialized['status'], $initialized['body']);

        $adminDeleted = $this->postForm([
            'action' => 'adminDeleteMaterialItems',
            'admin_password' => 'admin-secret',
            'ids' => (string)$itemId,
        ]);
        $this->assertSame(200, $adminDeleted['status'], $adminDeleted['body']);
    }

    private function uploadMaterial(string $tagId, string $termId, string $author, string $token): int
    {
        $path = tempnam(sys_get_temp_dir(), 'threadforge-material-');
        $zipPath = $path . '.zip';
        rename($path, $zipPath);
        file_put_contents($zipPath, 'test archive');
        $audioPath = $path . '.mp3';
        file_put_contents($audioPath, 'test audio');
        try {
            $response = $this->postForm([
                'action' => 'createMaterialItem',
                'auth_token' => $token,
                'name' => 'Material ' . uniqid(),
                'author_name' => $author,
                'notes' => 'integration test',
                'tag_id' => $tagId,
                'password' => 'postkey',
                'terms' => json_encode([$termId => true]),
                'archive' => new CURLFile($zipPath, 'application/zip', 'sample.zip'),
                'audio[0]' => new CURLFile($audioPath, 'audio/mpeg', 'sample.mp3'),
            ]);
        } finally {
            @unlink($zipPath);
            @unlink($audioPath);
        }
        $this->assertSame(200, $response['status'], $response['body']);
        $items = $this->getJson(['action' => 'listMaterialItems'])['json']['items'];
        return (int)max(array_column($items, 'id'));
    }

    private function startServer(): void
    {
        $port = $this->findFreePort();
        $this->baseUrl = 'http://127.0.0.1:' . $port . '/api.php';
        $serverRoot = dirname(__DIR__);
        $bootstrap = __DIR__ . '/materials-bootstrap.php';
        $command = sprintf(
            '"%s" -d auto_prepend_file="%s" -S 127.0.0.1:%d -t "%s"',
            PHP_BINARY,
            $bootstrap,
            $port,
            $serverRoot
        );
        $this->serverProcess = proc_open($command, [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, $serverRoot);
        $this->assertIsResource($this->serverProcess);
        $status = proc_get_status($this->serverProcess);
        $this->serverProcessId = (int)($status['pid'] ?? 0);
        $deadline = microtime(true) + 5;
        do {
            try {
                if ($this->getJson(['action' => 'version'])['status'] === 200) return;
            } catch (RuntimeException) {
                usleep(100000);
            }
        } while (microtime(true) < $deadline);
        $this->fail('PHP server did not start.');
    }

    private function stopServer(): void
    {
        if ($this->serverProcessId > 0 && PHP_OS_FAMILY === 'Windows') {
            exec('taskkill /F /T /PID ' . $this->serverProcessId . ' >NUL 2>NUL');
        }
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }
    }

    private function getJson(array $query): array
    {
        return $this->curl($this->baseUrl . '?' . http_build_query($query));
    }

    private function postForm(array $fields): array
    {
        return $this->curl($this->baseUrl, $fields);
    }

    private function curl(string $url, ?array $fields = null): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        if ($fields !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        }
        $raw = curl_exec($curl);
        if ($raw === false) {
            throw new RuntimeException(curl_error($curl));
        }
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int)curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        $body = substr($raw, $headerSize);
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    }

    private function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) throw new RuntimeException($error, $errno);
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int)substr(strrchr((string)$name, ':'), 1);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) return;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
