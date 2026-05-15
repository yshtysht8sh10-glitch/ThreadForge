# テスト

[English testing notes](../TESTING.md)

## フロントエンド

使用ツール:

- Vitest
- React Testing Library
- jsdom

主なテスト:

- `client/src/api.test.ts`
- `client/src/pages/HomePage.test.tsx`
- `client/src/pages/PostFormPage.test.tsx`
- `client/src/pages/ThreadPage.test.tsx`
- `client/src/pages/AdminPage.test.tsx`
- `client/src/tweet.test.ts`

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

- SQLite接続とテーブル初期化
- 画像保存ディレクトリ初期化
- `buildPost()` の画像URL変換
- `normalizeString()` の trim
- `findPostById()` の取得
- `findActivePostById()` がソフトデリート済み投稿を除外すること
- `deleteImage()` の動作
- `archiveExistingImage()` の履歴名退避
- `saveUploadedImage()` が通常ファイルに対して `null` を返すこと
- SNS文言生成、「最新はこちら」URL、投稿IDプレースホルダー置換、X文字数計算、SNS別省略、`_TWEND_`
- X、Bluesky、Mastodon、Misskey へのSNS画像転記
- SNSリアクション取得の手動実行、ローカルCron、APIキー付き外部定期実行、7日対象制限
- ローカルアーカイブログ取り込みの非破壊・重複スキップ動作
- 重複投稿判定
- URL正規化

実行:

```powershell
cd server
vendor\bin\phpunit
```

## 未実装または薄いテスト

- `server/api.php` のHTTP統合テスト
- 投稿作成、編集、削除の統合テスト
- 削除APIが物理削除ではなく `deleted_at` を設定することの統合テスト
- 実アップロードによる画像保存成功パス
- 画像差し替え時の履歴保持をHTTP経由で確認する統合テスト
- 検索APIの境界値テスト
- パスワード不一致、存在しない投稿などのエラー系テスト
- Laravel側のテスト
