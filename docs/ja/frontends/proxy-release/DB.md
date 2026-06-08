# proxy-release DB

[DB索引へ戻る](../../DB.md)

フロントID: `proxy-release`

## ランタイムファイル

開発環境:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

リリースZip配置先:

```text
database.sqlite
storage/data/
```

## 現在のスキーマ

現時点では `server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

想定データ:

- 親投稿を代理公開のリリース項目として扱う
- パッケージや画像はこのフロント専用のストレージに保存する
- 必要になればリリース用メタデータの追加を検討する

## 分離ルール

`proxy-release` は他フロントのDBやストレージを読み書きしません。
