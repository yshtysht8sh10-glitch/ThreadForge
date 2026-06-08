# materials-library DB

[DB索引へ戻る](../../DB.md)

フロントID: `materials-library`

## ランタイムファイル

開発環境:

```text
server/runtime/materials-library/database.sqlite
server/runtime/materials-library/storage/data/
```

リリースZip配置先:

```text
database.sqlite
storage/data/
```

## 現在のスキーマ

現時点では `server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

想定データ:

- 親投稿を素材または素材グループとして扱う
- ファイルとプレビュー画像はこのフロント専用ストレージに保存する
- 必要になれば利用条件/ライセンス用フィールドを追加する

## 分離ルール

`materials-library` は他フロントのDBやストレージを読み書きしません。
