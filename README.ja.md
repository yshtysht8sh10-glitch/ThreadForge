# ThreadForge

[English README](README.md)

ThreadForge は、作品投稿、画像、返信、簡単リアクション、ユーザー設定、管理、バックアップ、SNS 連携をまとめて扱う、レンタルサーバー向けのスレッド式掲示板エンジンです。

## 運用版

現在の運用開始版は `0.9.0` です。

- 配布 Zip を展開して、レンタルサーバーの公開ディレクトリへアップロードして使います。
- 初回アクセス時に、書き込み権限があれば SQLite DB が自動作成されます。
- 投稿データ、設定、ユーザー情報、画像は DB と `storage/data/` に保存されます。
- 更新前には、管理画面の「フルバックアップ インポート/エクスポート」からバックアップ Zip を取得してください。

## ディレクトリ構成

- `client/`: React / TypeScript / Vite フロントエンド
- `server/`: PHP API、SQLite、サーバー側処理
- `docs/`: 仕様、API、DB、運用、テスト関連ドキュメント
- `tools/`: 配布 Zip 作成、BBSNote/ローカルアーカイブ取り込み、修復などの運用ツール
- `release/`: 配布 Zip の出力先

## ローカル起動

フロントエンド:

```powershell
cd client
copy .env.example .env
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

バックエンド:

```powershell
cd server
composer install
php -S 127.0.0.1:8000 -t .
```

ブラウザで次を開きます。

```text
http://127.0.0.1:5173
```

## レンタルサーバーへの設置

配布 Zip を展開し、`threadforge-<version>` ディレクトリの中身を公開ディレクトリへアップロードします。

```text
threadforge-<version>/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

`storage/data/` は画像保存先です。必要に応じてレンタルサーバー側で書き込み権限を付けてください。

## 更新手順

1. 管理画面のフルバックアップを取得します。
2. 現在の `database.sqlite` と `storage/data/` を別途退避します。
3. 新しい配布 Zip のファイルをアップロードします。
4. `database.sqlite` と `storage/data/` は消さずに残します。
5. 画面を開き、投稿一覧、画像、管理画面、バックアップを確認します。

上書き時に DB と画像を消すと、投稿、返信、設定、ユーザー情報が失われます。

## 主な機能

- 投稿、返信、画像アップロード、画像差し替え
- 簡単リアクション、コメント、閲覧数、順位
- ユーザーログイン、アイコン、ユーザー設定、自分の作品登録
- 投稿一覧の年/月フィルタ、検索、サムネイル表示、スクロール読み込み
- 投稿編集、削除、管理者による一括削除、復元、消去、採番しなおし
- 掲示板設定、掲示板デザイン、色設定見本、インポート/エクスポート
- フルバックアップ Zip のインポート/エクスポート
- BBSNote/ローカルアーカイブの非破壊インポート、画像修復、直近差分更新
- X、Bluesky、Mastodon、Misskey 連携設定
- 親サイト SSO

## 運用ツール

配布 Zip と運用スクリプトは `tools/` にあります。詳しくは次を参照してください。

- `tools/README.md`
- `tools/README.ja.md`

配布 Zip は次で作成します。

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

出力先:

```text
release/threadforge-<version>.zip
```

## テスト

フロントエンド:

```powershell
cd client
npm test -- --run
npm run build
```

バックエンド:

```powershell
cd server
vendor/bin/phpunit
```

## ドキュメント

- `docs/ja/SPEC.md`: 日本語仕様
- `docs/ja/API.md`: API
- `docs/ja/DB.md`: DB とランタイムデータ
- `docs/ja/MIGRATION.md`: 移行メモ
- `docs/ja/TESTING.md`: テスト方針
- `CHANGELOG.ja.md`: 変更履歴
