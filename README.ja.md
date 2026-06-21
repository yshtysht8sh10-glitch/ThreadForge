# ThreadForge

[English README](README.md)

ThreadForge は、複数の掲示板/投稿系Webアプリを同じバックエンドで動かすための軽量エンジンです。画像掲示板、ファイルアップローダー、素材庫、代理公開所などをフロントエンド単位で分けて運用できます。

## フロントエンド

- `frontends/image-board/`: 画像投稿掲示板
- `frontends/file-uploader/`: 単体ファイルアップローダー
- `frontends/guide-posts/`: 書き物・ガイド投稿所
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
npm run dev:guide-posts
npm run dev:proxy-release
npm run dev:materials-library
```

バックエンド:

```powershell
cd server
composer install
php -S 127.0.0.1:8000 -t .
```

各フロントエンドは開発時に `server/runtime/<frontend-id>/database.sqlite` と `server/runtime/<frontend-id>/storage/data/` を使います。

## テスト

```powershell
npm run test:image-board
npm run build:frontends
cd server
vendor/bin/phpunit
```

## リリースZip

```powershell
npm run release:image-board
npm run release:file-uploader
npm run release:proxy-release
npm run release:materials-library
```

Zipは `release/threadforge-<frontend-id>-<version>.zip` に作られます。

- image-board: `11_image_board/`
- file-uploader: `12_file_uploader/`
- materials-library: `15_materials_library/`
- その他: frontend id の `-` を `_` に置き換えたディレクトリ

リリースZipには実運用の `database.sqlite` とアップロード済みファイルは含めません。運用データを移す場合は、各アプリの管理画面からフルバックアップをエクスポートし、移行先でインポートしてください。

## proxy-release

proxy-release は MUGEN代理公開所向けのフロントエンドです。

- zip投稿を受け付けます。
- AIR/SFFから待機GIFの自動生成を試みます。
- 手動の説明画像も選択できます。
- 上部メニューの `試遊` は管理画面で設定したURLへ飛びます。未設定時は無効表示になります。
- 管理画面のデザイン設定で、legacy風の緑基調に変更できます。

## ドキュメント

- `docs/index.html`: 日本語/英語切り替えドキュメント
- `docs/ja/README.md`: 日本語ドキュメント索引
- `docs/README.md`: 英語ドキュメント索引
- `CHANGELOG.md`: 変更履歴
- `CHANGELOG.ja.md`: 日本語変更履歴
