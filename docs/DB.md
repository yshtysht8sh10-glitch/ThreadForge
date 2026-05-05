# DB / ストレージ仕様

現行バックエンドは SQLite とファイルストレージを使います。

## SQLite

DB ファイル:

- `server/database.sqlite`

接続と初期化:

- `server/db.php`
- `getConnection()` が DB ファイルを作成し、`initializeDatabase()` でテーブルと画像保存ディレクトリを初期化します

## posts テーブル

```sql
CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  thread_id INTEGER NOT NULL,
  parent_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  url TEXT,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  image_path TEXT,
  password_hash TEXT,
  created_at TEXT NOT NULL,
  deleted_at TEXT,
  tweet_off INTEGER NOT NULL DEFAULT 0,
  tweet_text TEXT,
  tweet_url TEXT,
  tweet_like_count INTEGER NOT NULL DEFAULT 0,
  tweet_retweet_count INTEGER NOT NULL DEFAULT 0,
  tweet_comment_count INTEGER NOT NULL DEFAULT 0,
  tweet_impression_count INTEGER NOT NULL DEFAULT 0
)
```

## カラム

- `id`: 投稿 ID
- `thread_id`: 所属スレッド ID。親投稿では自身の ID
- `parent_id`: 親投稿 ID。親投稿では `0`
- `name`: 投稿者名
- `url`: 投稿者 URL / HOME
- `title`: タイトル
- `message`: 本文
- `image_path`: サーバ内部の画像保存パス
- `password_hash`: `password_hash()` で生成した編集、削除用ハッシュ
- `created_at`: `Asia/Tokyo` の日時文字列
- `deleted_at`: ソフト削除日時。未削除の場合は `null`
- `tweet_off`: Tweet OFF フラグ
- `tweet_text`: 自動生成 Tweet 文言
- `tweet_url`: Tweet 投稿先 URL
- `tweet_like_count`: いいね数
- `tweet_retweet_count`: リツイート数
- `tweet_comment_count`: コメント数
- `tweet_impression_count`: インプレッション数

## ソフト削除

投稿削除時は SQLite レコードを削除しません。`deleted_at` に `Asia/Tokyo` の日時文字列を保存します。

表示系 API は `deleted_at IS NULL` の投稿だけを返します。内部には投稿本文、画像パス、パスワードハッシュ、画像ファイルが残ります。

スレッド親投稿を削除した場合、同じ `thread_id` の返信もまとめてソフト削除します。返信を削除した場合はその返信だけをソフト削除します。

## 画像保存

保存先:

- `server/storage/data/`

API 応答時の公開パス:

- `/storage/data/{filename}`

ファイル名:

- 現行画像は投稿 ID と MIME type 由来の拡張子を組み合わせる
- 例: `123.png`, `123.jpg`, `123.gif`
- 差し替え時の旧画像は `123_YYYYMMDDHHMMSS.png` のような履歴名へ退避する
- API が返す `image_path` は最新画像のみを指す

許可 MIME type:

- `image/png`
- `image/jpeg`
- `image/gif`

## 未実装

- 画像サイズ上限の明示的なチェック
- 画像リサイズ
- サムネイル生成
- 画像メタデータ削除
- 履歴画像の一覧 API
- 履歴画像の自動削除、容量管理
- SQLite 以外の DB 本番対応
- マイグレーション管理
- 旧 CGI データのインポート
