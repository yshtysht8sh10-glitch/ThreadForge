<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../materials_legacy_import.php';

final class MaterialsLegacyImportTest extends TestCase
{
    private string $legacyRoot;

    protected function setUp(): void
    {
        if (is_file(DB_FILE)) {
            unlink(DB_FILE);
        }
        $this->removeDirectory(STORAGE_DIR);
        $this->legacyRoot = __DIR__ . '/legacy-materials-fixture';
        $this->removeDirectory($this->legacyRoot);
        mkdir($this->legacyRoot . '/zip/Test Author', 0775, true);
        mkdir($this->legacyRoot . '/img/Test Author', 0775, true);
        file_put_contents($this->legacyRoot . '/zip/Test Author/sample.zip', 'archive');
        file_put_contents($this->legacyRoot . '/img/Test Author/sample.gif', 'preview');
        file_put_contents($this->legacyRoot . '/img/Test Author/sample.mp3', 'audio');
        file_put_contents($this->legacyRoot . '/Sozaiko.html', <<<'HTML'
<div class="Heading1" id="HeadingEffect">1.エフェクト素材</div>
<table class="CellssMaterialTable">
  <a href="./img/Test%20Author/sample.gif"><img src="./img/Test%20Author/sample.gif"></a>
  <audio controls src="./img/Test%20Author/sample.mp3"></audio>
  <a href="./zip/Test%20Author/wrong.zip">Download</a>
  <table class="TermsTableInCellssMaterialTable">
    <tr><td class="TermsCellsLeft">改変</td><td class="TermsCellsRight">○</td></tr>
    <tr><td class="TermsCellsLeft">2次配布</td><td class="TermsCellsRight">△</td></tr>
  </table>
  <div class="CommentTableInCellssMaterialTable"><br>テスト素材です。</div>
  <div class="AuthorTableInCellssMaterialTable">作者：Test Author氏</div>
</table>
HTML);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->legacyRoot);
        if (is_file(DB_FILE)) {
            unlink(DB_FILE);
        }
        $this->removeDirectory(STORAGE_DIR);
    }

    public function testParserRepairsArchiveReferenceAndPreservesMetadata(): void
    {
        $items = parseLegacyMaterialsDirectory($this->legacyRoot);

        $this->assertCount(1, $items);
        $this->assertSame('Test Author/sample.zip', $items[0]['archive_relative']);
        $this->assertSame('Test Author', $items[0]['author_name']);
        $this->assertSame('エフェクト素材', $items[0]['tag_name']);
        $this->assertStringContainsString('テスト素材です。', $items[0]['notes']);
        $this->assertStringContainsString('利用条件「△」: 2次配布', $items[0]['notes']);
        $this->assertSame('○', $items[0]['terms']['改変']);
        $this->assertSame([realpath($this->legacyRoot . '/img/Test Author/sample.mp3')], $items[0]['media_paths']);
    }

    public function testImporterIsIdempotentAndCopiesFiles(): void
    {
        $pdo = getConnection();
        $first = importLegacyMaterials($pdo, $this->legacyRoot, STORAGE_DIR);
        $second = importLegacyMaterials($pdo, $this->legacyRoot, STORAGE_DIR);

        $this->assertSame(1, $first['imported']);
        $this->assertSame(0, $second['imported']);
        $this->assertSame(1, $second['skipped']);
        $row = $pdo->query(
            "SELECT * FROM material_items WHERE legacy_source = 'legacy-sozaiko:test author/sample.zip'"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row);
        $this->assertFileExists((string)$row['archive_path']);
        $this->assertFileExists((string)$row['image_path']);
        $this->assertSame(1, (int)$pdo->query(
            'SELECT COUNT(*) FROM material_item_terms WHERE item_id = ' . (int)$row['id'] . ' AND accepted = 1'
        )->fetchColumn());
        $media = $pdo->query(
            'SELECT * FROM material_media WHERE item_id = ' . (int)$row['id']
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($media);
        $this->assertSame('sample.mp3', $media['original_name']);
        $this->assertFileExists((string)$media['path']);
    }

    public function testPartialLegacyTermRemainsUnknownInsteadOfBecomingRejected(): void
    {
        $pdo = getConnection();
        importLegacyMaterials($pdo, $this->legacyRoot, STORAGE_DIR);
        $itemId = (int)$pdo->query('SELECT id FROM material_items LIMIT 1')->fetchColumn();
        $this->assertSame(1, (int)$pdo->query(
            'SELECT COUNT(*) FROM material_item_terms mit
             JOIN material_terms t ON t.id = mit.term_id
             WHERE mit.item_id = ' . $itemId . " AND t.label = '改変' AND mit.accepted = 1"
        )->fetchColumn());

        $html = file_get_contents($this->legacyRoot . '/Sozaiko.html');
        file_put_contents(
            $this->legacyRoot . '/Sozaiko.html',
            str_replace(
                '<td class="TermsCellsRight">○</td></tr>',
                '<td class="TermsCellsRight">△</td></tr>',
                (string)$html,
                $count
            )
        );
        $this->assertGreaterThan(0, $count);

        importLegacyMaterials($pdo, $this->legacyRoot, STORAGE_DIR);
        $answerCount = (int)$pdo->query(
            'SELECT COUNT(*) FROM material_item_terms mit
             JOIN material_terms t ON t.id = mit.term_id
             WHERE mit.item_id = ' . $itemId . " AND t.label = '改変'"
        )->fetchColumn();

        $this->assertSame(0, $answerCount);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
