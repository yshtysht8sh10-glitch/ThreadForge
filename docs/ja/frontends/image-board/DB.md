# image-board DB

[DB索引へ戻る](../../DB.md)
[English image-board DB notes](../../../frontends/image-board/DB.md)

フロントID: `image-board`

## ランタイムファイル

開発環境:

```text
server/runtime/image-board/database.sqlite
server/runtime/image-board/storage/data/
```

リリースZip配置先:

```text
11_image_board/database.sqlite
11_image_board/storage/data/
```

## 現在のスキーマ

`image-board` は現行の本番相当フロントで、`server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

主なテーブル:

- `posts`: 親投稿、返信、画像パス、削除状態、SNS連携情報、閲覧数など
- `users`: ログインID、表示名、投稿パスワード、HOME URL、アイコンなど
- `user_sessions`: ログイントークン
- `user_post_claims`: インポート投稿などを自分の作品として紐づける情報
- `post_revisions`: 編集履歴
- `access_counts`: アクセス数
- `settings`: 管理画面から保存する設定JSON

## 保存ルール

- 投稿画像とユーザーアイコンは有効なストレージ配下へ保存します。
- 開発環境では `server/runtime/image-board/storage/data/` を使います。
- リリース配置後は `storage/data/` を使います。
- バックアップは管理画面のフルバックアップを使います。
- DBとアップロード済みファイルはGit管理しません。

## 分離ルール

`image-board` は他フロントのDBやストレージを読み書きしません。
