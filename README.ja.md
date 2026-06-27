# ThreadForge

[English README](README.md)

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
