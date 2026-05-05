# API 仕様

現行 API は `server/api.php` です。REST 形式ではなく、`action` パラメータで処理を分岐します。

## 共通仕様

- レスポンス形式: JSON
- 文字コード: UTF-8
- CORS: `Access-Control-Allow-Origin: *`
- 許可メソッド: `GET`, `POST`, `OPTIONS`
- エラー時: HTTP 400/403/404 などと `{ "success": false, "message": "..." }`

## GET `?action=listThreads`

親投稿一覧を返します。

- 対象: `parent_id = 0`
- 並び順: `created_at DESC`
- `page`, `limit` によるページング。`limit` は最大 100
- レスポンス: `Post[]`

## GET `?action=getThread&id={id}`

指定スレッドを返します。

- `id`: スレッド親投稿 ID
- レスポンス: `{ "thread": Post, "replies": Post[] }`
- 返信の並び順: `created_at ASC`

## GET `?action=getPost&id={id}`

指定投稿を 1 件返します。

- `id`: 投稿 ID
- レスポンス: `Post`

## GET `?action=search&q={query}`

投稿を検索します。

- 対象カラム: `title`, `message`, `name`
- 検索方式: SQLite `LIKE` による部分一致
- 空文字: `[]`
- 並び順: `created_at DESC`
- `page`, `limit` によるページング。`limit` は最大 100
- レスポンス: `Post[]`

## POST `?action=createPost`

新規スレッドまたは返信を作成します。

Content-Type:

- `multipart/form-data`

フィールド:

- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `thread_id`: 任意。新規スレッドは `0` または未指定
- `parent_id`: 任意。新規スレッドは `0` または未指定
- `file`: 任意。PNG/JPEG/GIF
- `tweet_off`: 任意
- `tweet_url`: 任意
- `tweet_like_count`: 任意
- `tweet_retweet_count`: 任意
- `tweet_comment_count`: 任意
- `tweet_impression_count`: 任意

成功レスポンス:

```json
{
  "success": true,
  "message": "..."
}
```

## POST `?action=updatePost`

投稿を更新します。投稿時のパスワードが必要です。

Content-Type:

- `multipart/form-data`

フィールド:

- `id`: 必須
- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `file`: 任意。指定された場合のみ画像を差し替え。旧画像は履歴名へ退避して保持
- `tweet_off`: 任意
- `tweet_url`: 任意
- `tweet_like_count`: 任意
- `tweet_retweet_count`: 任意
- `tweet_comment_count`: 任意
- `tweet_impression_count`: 任意

パスワード不一致時は HTTP 403 を返します。

## POST `?action=deletePost`

投稿を削除します。投稿時のパスワードが必要です。

Content-Type:

- `multipart/form-data`

フィールド:

- `id`: 必須
- `password`: 必須

削除動作:

- SQLite レコードは削除せず、`deleted_at` を設定します
- スレッド親投稿を削除すると、そのスレッドの返信にも `deleted_at` を設定します
- 返信を削除すると、その返信のみに `deleted_at` を設定します
- 画像ファイルは削除しません
- 一覧、スレッド詳細、検索 API には削除済み投稿を返しません

## GET `?action=rss`

削除されていない親投稿を新着順に最大 30 件含む RSS 2.0 XML を返します。

## GET `?action=listDeletedPosts&admin_password={password}`

管理者向けに削除済み投稿を返します。`DOTEITA_ADMIN_PASSWORD` 環境変数と一致する `admin_password` が必要です。

## POST `?action=restorePost`

管理者向けに削除済み投稿を復元します。

フィールド:

- `id`: 必須
- `admin_password`: 必須

## Post 型

```ts
type Post = {
  id: number;
  thread_id: number;
  parent_id: number;
  name: string;
  url?: string | null;
  title: string;
  message: string;
  image_path?: string | null;
  created_at: string;
  deleted_at?: string | null;
  tweet_off?: boolean;
  tweet_text?: string | null;
  tweet_url?: string | null;
  tweet_like_count?: number;
  tweet_retweet_count?: number;
  tweet_comment_count?: number;
  tweet_impression_count?: number;
};
```

`password_hash` は API レスポンスには含めません。

## Laravel API

`server/laravel/routes/api.php` に Laravel 版のルートはありますが、現行フロントエンドの標準接続先ではありません。Laravel API は本番仕様としては未実装扱いです。

## 未実装 API

- 既存 Perl CGI データ移行 API
