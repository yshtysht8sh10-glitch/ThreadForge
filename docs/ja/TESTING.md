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
- `client/src/pages/LoginPage.test.tsx`
- `client/src/pages/UserPostsPage.test.tsx`
- `client/src/pages/BoardAnalyticsPage.test.tsx`
- `client/src/pages/SearchPage.test.tsx`
- `client/src/pages/RankingPage.test.tsx`
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
- `server/tests/ApiHttpIntegrationTest.php`

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
- SNSリアクション取得の手動実行、ローカルCron、APIキー付き外部定期実行、全対象更新
- ローカルアーカイブログ取り込みの非破壊・重複スキップ動作
- 重複投稿判定
- URL正規化
- `server/api.php` のHTTP統合テスト
- 投稿作成、編集、削除の統合テスト
- 削除APIが物理削除ではなく `deleted_at` を設定すること
- 実アップロードによる画像保存成功パス
- 画像差し替え時の履歴保持
- 検索APIの空文字、ページング、検索対象、ワイルドカード文字
- パスワード不一致、存在しない投稿などのエラー
- 管理者パスワード初期設定と認証
- 定型コメントの編集不可、管理者パスワードによる削除
- ユーザーID重複、ユーザー名30文字制限、自分の作品として登録/解除
- 管理画面の一括削除範囲指定、親投稿選択時の返信連動選択、選択解除
- 管理画面の復元/消去。カード選択/範囲選択、消去有効化、データ消去、番号ごと完全削除、表示番号の前詰め
- 管理画面のユーザー登録情報専用画面。表示前確認、編集、ログインパスワード再設定の確認入力/表示切替、消去有効化、情報消去、ユーザー番号ごと完全削除、ユーザー番号の前詰め
- 掲示板デザインの色設定見本への即時反映、通常/特殊投稿の配色、入力欄/ボタン/注意文/統計表示の見本、保存済み設定への復帰

実行:

```powershell
cd server
vendor\bin\phpunit
```

## カバレッジ確認メモ

- 2026-05-23時点で、主要なHTTP API、ストレージ、SNS文言、投稿/返信UI、検索、順位、管理画面、ログイン/ユーザー設定、個人/管理アナリティクス、管理者の復元/消去、ユーザー登録情報管理を自動テストで確認します。
- レイアウトの見た目、ブラウザのファイル保存ダイアログ、実SNS API連携、レンタルサーバー上の権限/パスは人の手で確認します。
- `server/laravel/` は移行用スケルトンです。現在の標準APIではないため、本番仕様の自動テスト対象外です。
