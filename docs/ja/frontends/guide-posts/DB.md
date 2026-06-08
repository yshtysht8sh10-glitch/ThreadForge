# guide-posts DB

[DB索引へ戻る](../../DB.md)

フロントID: `guide-posts`

## ランタイムファイル

開発環境:

```text
server/runtime/guide-posts/database.sqlite
server/runtime/guide-posts/storage/data/
```

リリースZip配置先:

```text
database.sqlite
storage/data/
```

## 現在のスキーマ

現時点では `server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

想定データ:

- 親投稿をガイドページまたは記事として扱う
- 添付メディアはこのフロント専用のストレージに保存する
- 共通設定だけで足りない場合、将来カテゴリやナビゲーション用の列/テーブルを検討する

## 分離ルール

`guide-posts` は他フロントのDBやストレージを読み書きしません。
