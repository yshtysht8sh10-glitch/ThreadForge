# ThreadForge 仕様索引

[English specification index](../SPEC.md)

ThreadForge は、ひとつの共通バックエンド上に複数のフロントエンドアプリを載せる構成です。共通仕様は共通ドキュメントに置き、各フロントエンド固有の仕様はフロントエンド別に管理します。

## フロントエンド別仕様

- [image-board 仕様](frontends/image-board/SPEC.md)
- [file-uploader 仕様](frontends/file-uploader/SPEC.md)
- [document-holder 仕様](frontends/document-holder/SPEC.md)
- [proxy-release 仕様](frontends/proxy-release/SPEC.md)
- [materials-library 仕様](frontends/materials-library/SPEC.md)

## 共通資料

- [API](API.md)
- [DB / ランタイムデータ](DB.md)
- [バックエンド構成](ARCHITECTURE.md)
- [フロントエンド構成](FRONTEND_ARCHITECTURE.md)
- [テスト](TESTING.md)

## 現在の状態

- `image-board` は画像投稿掲示板です。
- `file-uploader` は単体ファイルアップローダーです。
- `document-holder` は記事、HTMLフォルダ、制作メモを整理するドキュメントホルダーです。
- `proxy-release` はMUGENキャラクター/ステージ代理公開所です。
- `materials-library` は素材庫です。
- バックエンドコードは共通ですが、DBとストレージはフロントエンドごとに分離します。
