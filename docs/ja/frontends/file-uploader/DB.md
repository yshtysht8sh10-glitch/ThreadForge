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

共通の設定/管理テーブルに加え、`server/db.php` がアップローダー専用の `uploader_files` テーブルを作成します。

```sql
CREATE TABLE IF NOT EXISTS uploader_files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  stored_name TEXT NOT NULL,
  original_name TEXT NOT NULL,
  comment TEXT NOT NULL DEFAULT "",
  size_bytes INTEGER NOT NULL,
  delete_key_hash TEXT NOT NULL,
  created_at TEXT NOT NULL,
  deleted_at TEXT
);
```

- アップロードファイルはこのフロント専用の `storage/data/` に保存します。
- Delkey はハッシュ化して保存します。
- 元ファイル名と保存用ファイル名を分けて保持します。
- 許可拡張子と最大サイズはこのフロントの設定から読み込みます。
- `deleted_at` が設定された行は管理画面の復原・完全削除一覧に表示します。
- 復原では `deleted_at` を解除し、完全削除ではDB行と保存ファイルを削除します。
- 保守画面のフルバックアップZIPには、このフロントの `database.sqlite` と `storage/data/` 配下の全ファイルを含めます。

## 分離ルール

`file-uploader` は他フロントのDBやストレージを読み書きしません。
