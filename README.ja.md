# ThreadForge

[English README](README.md)

## アプリVersion

ThreadForgeはmonorepoですが、Versionはアプリ単位で独立管理します。

<!-- app-versions:start -->
| App | Current Version | 正本 |
| --- | --- | --- |
| Image Board | 0.9.7 | `frontends/image-board/package.json` |
| File Uploader | 1.0.2 | `frontends/file-uploader/package.json` |
| Document Holder | 0.9.7 | `frontends/document-holder/package.json` |
| Materials Library | 0.9.2 | `frontends/materials-library/package.json` |
| Proxy Release | 0.10.0 | `frontends/proxy-release/package.json` |
<!-- app-versions:end -->

正本の一覧は `npm run versions` で機械的に取得できます。他アプリの変更ではVersionを上げません。新しいtagは `<app-name>-vX.Y.Z`、GitHub Release名はアプリ名を含む形式にします。過去のtagは変更しません。

ThreadForge は、複数の掲示板/投稿系Webアプリを同じバックエンドで動かすための軽量エンジンです。画像掲示板、ファイルアップローダー、素材庫、代理公開所、ドキュメントホルダーなどをフロントエンド単位で分けて運用できます。

## フロントエンド

- `frontends/image-board/`: 画像投稿掲示板
- `frontends/file-uploader/`: 単体ファイルアップローダー
- `frontends/document-holder/`: 記事、HTMLフォルダ、制作メモを整理するドキュメントホルダー
- `frontends/proxy-release/`: MUGENキャラクター/ステージ代理公開所
- `frontends/materials-library/`: 素材庫
- `server/`: 共通PHP API、SQLite、テスト
- `docs/`: ドキュメント原稿と生成HTML
- `tools/`: リリースZip作成や移行用スクリプト
- `release/`: リリースZip出力先

## ローカル起動

フロントエンド:

```powershell
npm run dev:image-board
npm run dev:file-uploader
npm run dev:document-holder
npm run dev:proxy-release
npm run dev:materials-library
```

バックエンド:

```powershell
cd server
composer install
php -S 127.0.0.1:8000 -t .
```

各フロントエンドの開発時は `server/runtime/<frontend-id>/database.sqlite` と `server/runtime/<frontend-id>/storage/data/` を使います。

## テスト

```powershell
npm run test:image-board
npm run build:frontends
cd server
vendor/bin/phpunit
```

## ドキュメント

HTML版は `docs/index.html` から参照できます。Markdown原稿は `docs/` 配下にあります。
