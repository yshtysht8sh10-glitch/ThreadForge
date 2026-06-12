<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UploaderApiHttpIntegrationTest extends TestCase
{
    private $serverProcess = null;
    private int $serverProcessId = 0;
    private string $baseUrl = '';
    private string $runtimeDir;

    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('The curl extension is required for HTTP integration tests.');
        }

        $this->runtimeDir = __DIR__ . '/uploader-runtime';
        $this->removeDirectory($this->runtimeDir);
        mkdir($this->runtimeDir, 0775, true);
        $this->startServer();
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        $this->removeDirectory($this->runtimeDir);
    }

    public function testUploadListDeleteRestoreAndPurgeFlow(): void
    {
        $settings = $this->getJson(['action' => 'uploaderSettings']);
        $this->assertSame(200, $settings['status'], $settings['body']);
        $this->assertSame('ファイルアップローダー', $settings['json']['settings']['title']);
        $this->assertContains('txt', explode(' ', $settings['json']['settings']['allowedExtensions']));

        $path = tempnam(sys_get_temp_dir(), 'threadforge-uploader-');
        $txtPath = $path . '.txt';
        rename($path, $txtPath);
        file_put_contents($txtPath, 'uploader integration test');

        try {
            $uploaded = $this->postForm([
                'action' => 'uploadUploaderFile',
                'comment' => 'integration upload',
                'delete_key' => 'delete-secret',
                'file' => new CURLFile($txtPath, 'text/plain', 'sample.txt'),
            ]);
        } finally {
            @unlink($txtPath);
        }

        $this->assertSame(200, $uploaded['status'], $uploaded['body']);
        $this->assertTrue($uploaded['json']['success']);

        $listed = $this->getJson(['action' => 'listUploaderFiles']);
        $this->assertSame(200, $listed['status'], $listed['body']);
        $this->assertCount(1, $listed['json']['files']);
        $file = $listed['json']['files'][0];
        $this->assertSame('sample.txt', $file['originalName']);
        $this->assertSame('integration upload', $file['comment']);
        $this->assertFileExists($this->runtimeDir . '/storage/data/' . $file['filename']);

        $wrongKey = $this->postForm([
            'action' => 'deleteUploaderFile',
            'id' => (string)$file['id'],
            'delete_key' => 'wrong',
        ]);
        $this->assertSame(403, $wrongKey['status']);

        $deleted = $this->postForm([
            'action' => 'deleteUploaderFile',
            'id' => (string)$file['id'],
            'delete_key' => 'delete-secret',
        ]);
        $this->assertSame(200, $deleted['status'], $deleted['body']);
        $this->assertTrue($deleted['json']['success']);

        $initialized = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'admin-secret',
        ]);
        $this->assertSame(200, $initialized['status'], $initialized['body']);

        $deletedList = $this->getJson([
            'action' => 'listDeletedUploaderFiles',
            'admin_password' => 'admin-secret',
        ]);
        $this->assertCount(1, $deletedList['json']['files']);

        $restored = $this->postForm([
            'action' => 'restoreUploaderFiles',
            'admin_password' => 'admin-secret',
            'ids' => (string)$file['id'],
        ]);
        $this->assertSame(200, $restored['status'], $restored['body']);
        $this->assertCount(1, $this->getJson(['action' => 'listUploaderFiles'])['json']['files']);

        $adminDeleted = $this->postForm([
            'action' => 'adminDeleteUploaderFiles',
            'admin_password' => 'admin-secret',
            'ids' => (string)$file['id'],
        ]);
        $this->assertSame(200, $adminDeleted['status'], $adminDeleted['body']);

        $purged = $this->postForm([
            'action' => 'purgeUploaderFiles',
            'admin_password' => 'admin-secret',
            'ids' => (string)$file['id'],
        ]);
        $this->assertSame(200, $purged['status'], $purged['body']);
        $this->assertFalse(file_exists($this->runtimeDir . '/storage/data/' . $file['filename']));
    }

    private function startServer(): void
    {
        $port = $this->findFreePort();
        $this->baseUrl = 'http://127.0.0.1:' . $port . '/api.php';
        $serverRoot = dirname(__DIR__);
        $bootstrap = __DIR__ . '/uploader-bootstrap.php';
        $command = sprintf(
            '"%s" -d auto_prepend_file="%s" -S 127.0.0.1:%d -t "%s"',
            PHP_BINARY,
            $bootstrap,
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

        $this->assertIsResource($this->serverProcess);
        $status = proc_get_status($this->serverProcess);
        $this->serverProcessId = (int)($status['pid'] ?? 0);

        $deadline = microtime(true) + 5.0;
        do {
            try {
                if ($this->getJson(['action' => 'version'])['status'] === 200) {
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
        }
        $this->serverProcess = null;
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
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 10,
        ]);
        if ($method === 'POST') {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $fields);
        }
        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException($message);
        }
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return [
            'status' => $status,
            'body' => (string)$body,
            'json' => json_decode((string)$body, true),
        ];
    }

    private function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertNotFalse($socket);
        $name = stream_socket_get_name($socket, false);
        fclose($socket);
        $parts = explode(':', (string)$name);
        return (int)end($parts);
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
            $item->isDir() ? rmdir((string)$item) : unlink((string)$item);
        }
        rmdir($directory);
    }
}
