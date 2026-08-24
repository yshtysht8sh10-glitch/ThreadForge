<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProxyReleaseAdminSettingsHttpTest extends TestCase
{
    private $serverProcess = null;
    private int $serverProcessId = 0;
    private string $baseUrl = '';
    private string $runtimeDir;

    protected function setUp(): void
    {
        if (!extension_loaded('curl')) $this->markTestSkipped('The curl extension is required.');
        $this->runtimeDir = __DIR__ . '/proxy-release-settings-runtime';
        $this->removeDirectory($this->runtimeDir);
        mkdir($this->runtimeDir, 0775, true);
        $this->startServer();
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        $this->removeDirectory($this->runtimeDir);
    }

    public function testOnlyAdminCanSaveTokenAndPlaintextIsNeverReturned(): void
    {
        $unauthorizedRead = $this->getJson(['action' => 'getSettings']);
        self::assertSame(403, $unauthorizedRead['status']);
        $unauthorizedWrite = $this->postForm(['action' => 'updateSettings', 'settings' => '{}']);
        self::assertSame(403, $unauthorizedWrite['status']);

        $initialized = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'admin-secret',
        ]);
        self::assertSame(200, $initialized['status'], $initialized['body']);

        $admin = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin-secret']);
        self::assertFalse($admin['json']['webMugen']['tokenConfigured']);
        self::assertArrayNotHasKey('webMugenApiToken', $admin['json']['settings']['security']);
        $settings = $admin['json']['settings'];
        $settings['config']['webMugenApiUrl'] = $this->baseUrlForWebMugen();
        $settings['config']['webMugenStageId'] = 'fresh-clasic';
        $dummyToken = 'dummy-webmugen-token-32-characters';
        $saved = $this->postForm([
            'action' => 'updateSettings',
            'admin_password' => 'admin-secret',
            'settings_b64' => base64_encode((string)json_encode($settings)),
            'webmugen_api_token' => $dummyToken,
        ]);
        self::assertSame(200, $saved['status'], $saved['body']);

        $after = $this->getJson(['action' => 'getSettings', 'admin_password' => 'admin-secret']);
        self::assertTrue($after['json']['webMugen']['tokenConfigured']);
        self::assertStringNotContainsString($dummyToken, $after['body']);
        $public = $this->getJson(['action' => 'materialsSettings']);
        self::assertStringNotContainsString($dummyToken, $public['body']);

        $pdo = new PDO('sqlite:' . $this->runtimeDir . '/database.sqlite');
        $stored = json_decode((string)$pdo->query("SELECT value FROM settings WHERE key = 'security'")->fetchColumn(), true);
        self::assertSame($dummyToken, $stored['webMugenApiToken']);
    }

    public function testAdminCanInspectAndEditMaterialWhilePublicApiHidesInternalFields(): void
    {
        $initialized = $this->postForm([
            'action' => 'initializeAdminPassword',
            'new_admin_password' => 'admin-secret',
        ]);
        self::assertSame(200, $initialized['status'], $initialized['body']);

        $pdo = new PDO('sqlite:' . $this->runtimeDir . '/database.sqlite');
        $tagId = (int)$pdo->query('SELECT id FROM material_tags ORDER BY id LIMIT 1')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO material_items
             (id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
              password_hash, webmugen_character_id, webmugen_error, created_at, updated_at)
             VALUES (41, "Old Author", "Old Name", "Old notes", :tag_id, :path, "original.zip", 123,
              "", "proxy-release-41", :error, "2026-08-20 00:00:00", "2026-08-20 00:00:00")'
        )->execute([
            ':tag_id' => $tagId,
            ':path' => $this->runtimeDir . '/storage/data/material-41-archive.zip',
            ':error' => json_encode(['code' => 'old.error', 'message' => 'previous failure']),
        ]);

        $unauthorized = $this->getJson(['action' => 'getAdminMaterialItem', 'id' => 41]);
        self::assertSame(403, $unauthorized['status']);
        $admin = $this->getJson(['action' => 'getAdminMaterialItem', 'id' => 41, 'admin_password' => 'admin-secret']);
        self::assertSame(200, $admin['status'], $admin['body']);
        self::assertSame('material-41-archive.zip', $admin['json']['item']['archiveFile']);
        self::assertSame('proxy-release-41', $admin['json']['item']['webMugenCharacterId']);
        self::assertStringContainsString('previous failure', $admin['json']['item']['trialPlayError']);

        $publicBefore = $this->getJson(['action' => 'listMaterialItems']);
        self::assertSame(200, $publicBefore['status']);
        self::assertArrayNotHasKey('webMugenCharacterId', $publicBefore['json']['items'][0]);
        self::assertArrayNotHasKey('trialPlayError', $publicBefore['json']['items'][0]);
        self::assertStringNotContainsString((string)$this->runtimeDir, $publicBefore['body']);

        $invalid = $this->postForm([
            'action' => 'adminUpdateMaterialItem',
            'admin_password' => 'admin-secret',
            'id' => '41',
            'name' => 'Edited Name',
            'author_name' => 'Edited Author',
            'tag_id' => (string)$tagId,
            'play_url' => 'javascript:alert(1)',
        ]);
        self::assertSame(400, $invalid['status'], $invalid['body']);

        $playUrl = 'https://example.test/webmugen/?character=proxy-release-41&stage=cyber';
        $saved = $this->postForm([
            'action' => 'adminUpdateMaterialItem',
            'admin_password' => 'admin-secret',
            'id' => '41',
            'name' => 'Edited Name',
            'author_name' => 'Edited Author',
            'notes' => 'Edited notes',
            'tag_id' => (string)$tagId,
            'draft' => '0',
            'play_url' => $playUrl,
        ]);
        self::assertSame(200, $saved['status'], $saved['body']);
        self::assertSame('Edited Name', $saved['json']['item']['name']);
        self::assertSame($playUrl, $saved['json']['item']['playUrl']);
        self::assertNull($saved['json']['item']['trialPlayError']);

        $publicAfter = $this->getJson(['action' => 'listMaterialItems']);
        self::assertSame($playUrl, $publicAfter['json']['items'][0]['playUrl']);
        self::assertArrayNotHasKey('webMugenCharacterId', $publicAfter['json']['items'][0]);
        self::assertArrayNotHasKey('trialPlayError', $publicAfter['json']['items'][0]);
    }

    private function startServer(): void
    {
        $port = $this->findFreePort();
        $this->baseUrl = 'http://127.0.0.1:' . $port . '/api.php';
        $serverRoot = dirname(__DIR__);
        $bootstrap = __DIR__ . '/proxy-release-bootstrap.php';
        $command = sprintf('"%s" -d auto_prepend_file="%s" -S 127.0.0.1:%d -t "%s"', PHP_BINARY, $bootstrap, $port, $serverRoot);
        $this->serverProcess = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $serverRoot);
        self::assertIsResource($this->serverProcess);
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
        self::fail('PHP server did not start.');
    }

    private function baseUrlForWebMugen(): string
    {
        $parts = parse_url($this->baseUrl);
        return 'http://' . $parts['host'] . ':' . $parts['port'] . '/DotoEita/50_WEBMUGEN/api/catalog.php';
    }

    private function getJson(array $query): array { return $this->curl($this->baseUrl . '?' . http_build_query($query)); }
    private function postForm(array $fields): array { return $this->curl($this->baseUrl, $fields); }

    private function curl(string $url, ?array $fields = null): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        if ($fields !== null) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
        }
        $body = curl_exec($curl);
        if ($body === false) throw new RuntimeException(curl_error($curl));
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return ['status' => $status, 'body' => (string)$body, 'json' => json_decode((string)$body, true)];
    }

    private function stopServer(): void
    {
        if ($this->serverProcessId > 0 && PHP_OS_FAMILY === 'Windows') exec('taskkill /F /T /PID ' . $this->serverProcessId . ' >NUL 2>NUL');
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }
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
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($path);
    }
}
