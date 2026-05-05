# テスト仕様

## フロントエンド

使用ツール:

- Vitest
- React Testing Library
- jsdom

主なテスト:

- `client/src/api.test.ts`
- `client/src/pages/HomePage.test.tsx`

実行:

```powershell
cd client
npm test
```

## バックエンド

使用ツール:

- PHPUnit 9

主なテスト:

- `server/tests/StorageLayerTest.php`

検証対象:

- SQLite 接続とテーブル初期化
- 画像保存ディレクトリ初期化
- `buildPost()` の画像 URL 変換
- `normalizeString()` の trim
- `findPostById()` の取得
- `findActivePostById()` がソフト削除済み投稿を除外すること
- `deleteImage()` の削除動作
- `archiveExistingImage()` の履歴名退避
- `saveUploadedImage()` が通常ファイルに対して `null` を返すこと
- Tweet 文言生成、文字数計算、`_TWEND_`
- 重複投稿判定
- URL 正規化

実行:

```powershell
cd server
vendor\bin\phpunit
```

## 未実装または不足しているテスト

- `server/api.php` の HTTP 経由テスト
- 投稿作成、編集、削除の統合テスト
- 削除 API が物理削除せず `deleted_at` を設定する統合テスト
- 実アップロードによる画像保存成功パス
- 画像差し替え時の履歴保持を HTTP 経由で確認する統合テスト
- 検索 API の境界値テスト
- パスワード不一致、存在しない投稿などのエラー系テスト
- Laravel 側のテスト
