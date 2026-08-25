<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api.php';

final class WebMugenProxyIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        initializeDatabase($this->pdo);
        $tagId = (int)$this->pdo->query('SELECT id FROM material_tags ORDER BY id LIMIT 1')->fetchColumn();
        $this->pdo->prepare(
            'INSERT INTO material_items
             (id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
              password_hash, created_at, updated_at)
             VALUES (17, "Author", "Fighter", "", :tag_id, "C:/proxy/storage/uploaded_938472.zip", "fighter.zip", 10,
              "hash", "2026-08-23 00:00:00", "2026-08-23 00:00:00")'
        )->execute([':tag_id' => $tagId]);
        $settings = loadSettings($this->pdo);
        $settings['security']['webMugenApiToken'] = 'database-token-value';
        $settings['config']['webMugenApiUrl'] = 'https://example.test/webmugen/api/catalog.php';
        $settings['config']['webMugenStageId'] = 'fresh-clasic';
        $settings['config']['webMugenCharacterId'] = 't-h-m-a';
        saveSettings($this->pdo, $settings);
        putenv('THREADFORGE_WEBMUGEN_CATALOG_SECRET=fallback-token-value');
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.test';
    }

    protected function tearDown(): void
    {
        putenv('THREADFORGE_WEBMUGEN_CATALOG_SECRET');
        putenv('THREADFORGE_WEBMUGEN_CATALOG_API_URL');
    }

    public function testProxyPublishStoresCharacterAndPlayUrl(): void
    {
        $requester = function (string $url, array $headers, string $body): array {
            self::assertSame('https://example.test/webmugen/api/catalog.php?action=publish-character', $url);
            self::assertContains('Authorization: Bearer database-token-value', $headers);
            self::assertContains('X-WebMUGEN-Token: database-token-value', $headers);
            self::assertStringNotContainsString('database-token-value', $body);
            self::assertSame([
                'publicationId' => '17',
                'archiveFile' => 'uploaded_938472.zip',
                'stageId' => 'fresh-clasic',
            ], json_decode($body, true));
            return ['success' => true, 'body' => [
                'success' => true,
                'characterId' => 'proxy-release-17',
                'characterPath' => '/DotoEita/16_proxy_release/storage/data/uploaded_938472.zip',
                'playUrl' => 'https://example.test/webmugen/index.html?character=proxy-release-17&stage=fresh-clasic',
            ]];
        };

        $result = publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', $requester);
        self::assertTrue($result['success']);
        $row = $this->pdo->query('SELECT webmugen_character_id, webmugen_play_url, webmugen_error FROM material_items WHERE id = 17')->fetch(PDO::FETCH_ASSOC);
        self::assertSame('proxy-release-17', $row['webmugen_character_id']);
        self::assertStringContainsString('character=proxy-release-17', $row['webmugen_play_url']);
        self::assertNull($row['webmugen_error']);
    }

    public function testStagePublicationUsesPublishStageAndConfiguredCharacter(): void
    {
        $this->pdo->prepare('INSERT INTO material_tags (name, sort_order, created_at) VALUES ("MUGENステージ", 99, "2026-08-25 00:00:00")')->execute();
        $stageTagId = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare(
            'INSERT INTO material_items
             (id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
              password_hash, created_at, updated_at)
             VALUES (22, "Author", "Arena", "", :tag_id, "C:/proxy/storage/material-22-archive.zip", "arena.zip", 10,
              "hash", "2026-08-25 00:00:00", "2026-08-25 00:00:00")'
        )->execute([':tag_id' => $stageTagId]);

        $requester = function (string $url, array $headers, string $body): array {
            self::assertSame('https://example.test/webmugen/api/catalog.php?action=publish-stage', $url);
            self::assertSame([
                'publicationId' => '22',
                'archiveFile' => 'material-22-archive.zip',
                'characterId' => 't-h-m-a',
            ], json_decode($body, true));
            return ['success' => true, 'body' => [
                'success' => true,
                'stageId' => 'proxy-release-22',
                'stagePath' => '/DotoEita/16_proxy_release/storage/data/material-22-archive.zip',
                'playUrl' => 'https://example.test/webmugen/index.html?character=t-h-m-a&stage=proxy-release-22',
            ]];
        };

        $result = publishProxyReleaseToWebMugen($this->pdo, 22, 'proxy-release', $requester);
        self::assertTrue($result['success']);
        self::assertSame('stage', $result['kind']);
        self::assertSame('proxy-release-22', $result['stageId']);
        $row = $this->pdo->query('SELECT webmugen_character_id, webmugen_play_url FROM material_items WHERE id = 22')->fetch(PDO::FETCH_ASSOC);
        self::assertSame('proxy-release-22', $row['webmugen_character_id']);
        self::assertStringContainsString('stage=proxy-release-22', $row['webmugen_play_url']);
    }

    public function testCatalogEndpointRejectsAnExternalHost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        webMugenCatalogEndpointFromValue('https://attacker.example/api/catalog.php', [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'example.test',
        ]);
    }

    public function testProxyPublishFailureKeepsReleaseAndStoresMachineReadableError(): void
    {
        $result = publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', static fn(): array => [
            'success' => false,
            'message' => 'connection failed',
        ]);
        self::assertFalse($result['success']);
        self::assertSame('api.failed', $result['code']);
        $row = $this->pdo->query('SELECT id, webmugen_play_url, webmugen_error FROM material_items WHERE id = 17')->fetch(PDO::FETCH_ASSOC);
        self::assertSame(17, (int)$row['id']);
        self::assertNull($row['webmugen_play_url']);
        self::assertSame(['code' => 'api.failed', 'message' => 'connection failed'], json_decode($row['webmugen_error'], true));
    }

    public function testProxyPublishSummarizesAnHtml404WithoutStoringTheResponsePage(): void
    {
        $html = '<!DOCTYPE html><html><head><title>404 Error - Not Found</title></head><body>' . str_repeat('missing ', 200) . '</body></html>';
        $result = publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', static fn(): array => [
            'success' => false,
            'message' => 'API returned an error. HTTP 404 ' . $html,
        ]);

        self::assertFalse($result['success']);
        self::assertSame('api.failed', $result['code']);
        self::assertStringContainsString('HTTP 404', $result['message']);
        self::assertStringNotContainsString('<html', strtolower($result['message']));
        self::assertLessThan(200, mb_strlen($result['message'], 'UTF-8'));
        $stored = (string)$this->pdo->query('SELECT webmugen_error FROM material_items WHERE id = 17')->fetchColumn();
        self::assertStringNotContainsString('<html', strtolower($stored));
    }

    public function testRepeatedPublishUsesStablePublicationIdAndUpsertsOneCatalogEntry(): void
    {
        $catalog = [];
        $calls = 0;
        $requester = static function (string $url, array $headers, string $body) use (&$catalog, &$calls): array {
            $calls++;
            $payload = json_decode($body, true);
            $characterId = 'proxy-release-' . $payload['publicationId'];
            $catalog[$characterId] = ['archiveFile' => $payload['archiveFile'], 'stageId' => $payload['stageId']];
            return ['success' => true, 'body' => [
                'success' => true,
                'characterId' => $characterId,
                'characterPath' => '/proxy/' . $payload['archiveFile'],
                'playUrl' => 'https://example.test/play?character=' . $characterId . '&stage=' . $payload['stageId'],
            ]];
        };

        self::assertTrue(publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', $requester)['success']);
        self::assertTrue(publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', $requester)['success']);
        self::assertSame(2, $calls, 'Re-register must call the upsert API again so ZIP changes can be applied.');
        self::assertCount(1, $catalog);
        self::assertArrayHasKey('proxy-release-17', $catalog);
    }

    public function testPublishRecoversPlayUrlWhenCatalogAlreadyContainsStableId(): void
    {
        $catalog = ['proxy-release-17' => ['archiveFile' => 'old.zip']];
        $requester = static function (string $url, array $headers, string $body) use (&$catalog): array {
            $payload = json_decode($body, true);
            $characterId = 'proxy-release-' . $payload['publicationId'];
            $catalog[$characterId] = ['archiveFile' => $payload['archiveFile']];
            return ['success' => true, 'body' => [
                'success' => true,
                'characterId' => $characterId,
                'characterPath' => '/proxy/' . $payload['archiveFile'],
                'playUrl' => 'https://example.test/play?character=' . $characterId,
            ]];
        };

        $this->pdo->exec('UPDATE material_items SET webmugen_play_url = null WHERE id = 17');
        $result = publishProxyReleaseToWebMugen($this->pdo, 17, 'proxy-release', $requester);
        self::assertTrue($result['success']);
        self::assertCount(1, $catalog);
        self::assertSame('uploaded_938472.zip', $catalog['proxy-release-17']['archiveFile']);
        self::assertStringContainsString('proxy-release-17', (string)$this->pdo->query('SELECT webmugen_play_url FROM material_items WHERE id = 17')->fetchColumn());
    }

    public function testBulkPublishContinuesAfterFailureAndSkipsExistingPlayUrls(): void
    {
        $tagId = (int)$this->pdo->query('SELECT id FROM material_tags ORDER BY id LIMIT 1')->fetchColumn();
        $insert = $this->pdo->prepare(
            'INSERT INTO material_items
             (id, author_name, name, notes, tag_id, archive_path, archive_original_name, archive_size_bytes,
              password_hash, webmugen_play_url, created_at, updated_at)
             VALUES (:id, "Author", :name, "", :tag_id, :path, :file, 10, "hash", :play_url, "2026-08-23 00:00:00", "2026-08-23 00:00:00")'
        );
        $insert->execute([':id' => 18, ':name' => 'Broken', ':tag_id' => $tagId, ':path' => 'C:/proxy/storage/broken.zip', ':file' => 'broken.zip', ':play_url' => null]);
        $insert->execute([':id' => 19, ':name' => 'Existing', ':tag_id' => $tagId, ':path' => 'C:/proxy/storage/existing.zip', ':file' => 'existing.zip', ':play_url' => 'https://example.test/already']);
        $calls = [];
        $requester = static function (string $url, array $headers, string $body) use (&$calls): array {
            $payload = json_decode($body, true);
            $calls[] = $payload['publicationId'];
            if ($payload['publicationId'] === '18') return ['success' => false, 'message' => 'ZIP contains no valid Character DEF'];
            $characterId = 'proxy-release-' . $payload['publicationId'];
            return ['success' => true, 'body' => [
                'success' => true,
                'characterId' => $characterId,
                'characterPath' => '/proxy/' . $payload['archiveFile'],
                'playUrl' => 'https://example.test/play?character=' . $characterId,
            ]];
        };

        $summary = publishMissingProxyReleasesToWebMugen($this->pdo, 'proxy-release', $requester);
        self::assertSame(['17', '18'], $calls);
        self::assertSame(2, $summary['target']);
        self::assertSame(1, $summary['succeeded']);
        self::assertSame(1, $summary['failed']);
        self::assertSame(18, $summary['failures'][0]['id']);
        self::assertStringContainsString('valid Character DEF', $summary['failures'][0]['message']);
        self::assertSame('https://example.test/already', $this->pdo->query('SELECT webmugen_play_url FROM material_items WHERE id = 19')->fetchColumn());
    }

    public function testPublicItemOmitsWebMugenInternalFields(): void
    {
        $this->pdo->exec("UPDATE material_items SET webmugen_character_id = 'proxy-release-17', webmugen_play_url = 'https://example.test/play', webmugen_error = '{\"code\":\"old\"}' WHERE id = 17");
        $row = findMaterialItem($this->pdo, 17, false);
        $public = buildMaterialItem($this->pdo, (array)$row);
        self::assertSame('https://example.test/play', $public['playUrl']);
        self::assertArrayNotHasKey('webMugenCharacterId', $public);
        self::assertArrayNotHasKey('trialPlayError', $public);
        self::assertArrayNotHasKey('archivePath', $public);

        $admin = buildMaterialItem($this->pdo, (array)$row, true);
        self::assertSame('proxy-release-17', $admin['webMugenCharacterId']);
        self::assertSame('uploaded_938472.zip', $admin['archiveFile']);
        self::assertArrayHasKey('trialPlayError', $admin);
    }
}
