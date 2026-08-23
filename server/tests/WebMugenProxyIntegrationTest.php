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
}
