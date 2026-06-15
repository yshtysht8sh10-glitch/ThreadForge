# ThreadForge

[English README](README.md)

ThreadForge は、作品投稿、画像、返信、簡単リアクション、ユーザー設定、管理、バックアップ、SNS 連携をまとめて扱う、レンタルサーバー向けのスレッド式掲示板エンジンです。

## 運用版

画像掲示板の運用版は `0.9.7`、単体file-uploaderは `1.0.0`、materials-libraryの公開プレビュー版は `0.9.2` です。

- 配布 Zip を展開して、レンタルサーバーの公開ディレクトリへアップロードして使います。
- 初回アクセス時に、書き込み権限があれば SQLite DB が自動作成されます。
- 投稿データ、設定、ユーザー情報、画像は DB と `storage/data/` に保存されます。
- 更新前には、管理画面の「フルバックアップ インポート/エクスポート」からバックアップ Zip を取得してください。

## ディレクトリ構成

- `frontends/image-board/`: 現在の画像投稿掲示板用 React / TypeScript / Vite フロントエンド
- `frontends/shared/`: 今後の複数フロントで使う共通フロント部品
- `frontends/file-uploader/`: ファイルアップローダ用フロント
- `frontends/guide-posts/`: MUGEN/ドット絵制作指南ページ投稿所予定地
- `frontends/proxy-release/`: MUGENキャラ代理公開所予定地
- `frontends/materials-library/`: 圧縮ファイルを用途と作者で整理する素材庫
- `server/`: PHP API、SQLite、サーバー側処理
- `docs/index.html`: 日本語/English切替式ドキュメント
- `docs/`: Markdown原稿、生成HTML、仕様、API、DB、運用、テスト関連資料
- `tools/`: 配布 Zip 作成、BBSNote/ローカルアーカイブ取り込み、修復などの運用ツール
- `release/`: 配布 Zip の出力先

## ローカル起動

ルートからのショートカット:

```powershell
npm run dev:image-board
npm run dev:file-uploader
npm run dev:guide-posts
npm run dev:proxy-release
npm run dev:materials-library
```

フロントエンド:

```powershell
cd frontends/image-board
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

配布 Zip を展開し、含まれる公開用ディレクトリを `/DotoEita/` へアップロードします。

```text
11_image_board/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

image-board の Zip には `11_image_board/`、file-uploader の Zip には `12_file_uploader/`、
materials-library の Zip には `15_materials_library/` が入ります。

`storage/data/` は画像保存先です。必要に応じてレンタルサーバー側で書き込み権限を付けてください。

## SNSリアクションの定期更新

管理画面の掲示板設定に、Cron用ファイルパスとAPI定期実行URLが表示されます。

- レンタルサーバーのCronがファイル実行に対応している場合は、Cron用ファイルパスの `cron.php` を登録します。
- 外部URL実行型のCronを使う場合は、API定期実行URLを登録します。
- `cron.php` はブラウザからそのまま開くと拒否されますが、`?api_key=...` 付きで呼び出された場合だけSNSリアクション更新を実行できます。
- 実行結果は管理画面の保守にあるSNSログで確認できます。`cron social reaction ...` または `cron web social reaction ...` が出ていればCron経由で実行されています。

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
- 親サイト SSO（SSO利用時はThreadForge側の新規アカウント作成を停止）

## 運用ツール

配布 Zip と運用スクリプトは `tools/` にあります。詳しくは次を参照してください。

- `tools/README.md`
- `tools/README.ja.md`

配布 Zip は次で作成します。

```powershell
npm run release:image-board
npm run release:file-uploader
npm run release:materials-library
```

出力先:

```text
release/threadforge-<frontend-id>-<version>.zip
```

## テスト

リポジトリルートから:

```powershell
npm run test:image-board
npm run build:image-board
npm run build:frontends
```

フロントエンド:

```powershell
cd frontends/image-board
npm test -- --run
npm run build
```

バックエンド:

```powershell
cd server
vendor/bin/phpunit
```

## ドキュメント

- `docs/index.html`: 日英統合ドキュメント索引
- `docs/SPEC.html`: 仕様
- `docs/FRONTEND_ARCHITECTURE.html`: 複数フロント構成と移行方針
- `docs/API.html`: API
- `docs/DB.html`: DB とランタイムデータ
- `docs/MIGRATION.html`: 移行メモ
- `docs/TESTING.html`: テスト方針
- `CHANGELOG.ja.md`: 変更履歴
