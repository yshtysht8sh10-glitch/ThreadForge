# ThreadForge 仕様索引

[English specification index](../SPEC.md)

ThreadForge は、1つの共通バックエンドの上に5つの単体フロントアプリを載せる構成へ移行しています。

共通契約は共通ドキュメントに置き、各フロント固有の仕様はフロント別に管理します。

## フロント別仕様

- [image-board 仕様](frontends/image-board/SPEC.md)
- [file-uploader 仕様](frontends/file-uploader/SPEC.md)
- [guide-posts 仕様](frontends/guide-posts/SPEC.md)
- [proxy-release 仕様](frontends/proxy-release/SPEC.md)
- [materials-library 仕様](frontends/materials-library/SPEC.md)

## 共通契約

- [API](API.md)
- [DBランタイム索引](DB.md)
- [バックエンド/フロントエンド構成](ARCHITECTURE.md)
- [フロントエンド構成](FRONTEND_ARCHITECTURE.md)
- [テスト](TESTING.md)

## 現在の状態

- `image-board` は現行の本番フロントで、詳細仕様を持ちます。
- `file-uploader` 1.0.0 は、`image-board` に続いて完成した最初の単体フロントです。
- `guide-posts`、`proxy-release`、`materials-library` は legacy をもとにした初期フロントです。
- バックエンドコードは共通ですが、開発中もリリース配置後もDBとストレージはフロントごとに独立します。
