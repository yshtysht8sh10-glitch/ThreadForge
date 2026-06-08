# file-uploader DB

[DB索引へ戻る](../../DB.md)

フロントID: `file-uploader`

## ランタイムファイル

開発環境:

```text
server/runtime/file-uploader/database.sqlite
server/runtime/file-uploader/storage/data/
```

リリースZip配置先:

```text
database.sqlite
storage/data/
```

## 現在のスキーマ

現時点では `server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

想定データ:

- アップロードファイルは `storage/data/` に保存する
- 専用アップロードテーブルを導入するまでは、ファイル項目を投稿行として扱う
- コメント、削除キー/パスワード、サイズ情報、ページングはこのフロントの振る舞いとして扱う

## 分離ルール

`file-uploader` は他フロントのDBやストレージを読み書きしません。
