# API リファレンス

[English API reference](../API.md)

現在の API は `server/api.php` に実装されています。REST形式ではなく、`action` パラメータで処理を振り分けます。

## 共通仕様

- レスポンス形式: 特記がない限り JSON
- 文字コード: UTF-8
- CORS: `Access-Control-Allow-Origin: *`
- 許可メソッド: `GET`, `POST`, `OPTIONS`
- エラー時は HTTP 400/403/404 と `{ "success": false, "message": "..." }` を返します

## GET `?action=listThreads`

親投稿一覧を返します。

- 対象: `parent_id = 0`
- 並び順: `created_at DESC`
- `page`, `limit` に対応。`limit` の最大値は100
- レスポンス: `Post[]`

## GET `?action=getThread&id={id}`

スレッドと返信を返します。

- `id`: 親投稿ID
- レスポンス: `{ "thread": Post, "replies": Post[] }`
- 返信は `created_at ASC`

## GET `?action=getPost&id={id}`

投稿を1件返します。

- `id`: 投稿ID
- レスポンス: `Post`

## GET `?action=search&q={query}`

投稿を検索します。

- 対象カラム: `title`, `message`, `name`
- 検索方法: SQLite `LIKE`
- 空文字は `[]`
- 並び順: `created_at DESC`
- `page`, `limit` に対応。`limit` の最大値は100
- レスポンス: `Post[]`

## POST `?action=createPost`

新規の親投稿または返信を作成します。

Content-Type:

- `multipart/form-data`

フィールド:

- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `thread_id`: 任意。新規親投稿では `0` または未指定
- `parent_id`: 任意。新規親投稿では `0` または未指定
- `file`: 任意。PNG/JPEG/GIF。親投稿のみ
- `gdgd`: 任意。親投稿のみ
- `tweet_off`: 任意。親投稿のみ。UIでは `SNS転記OFF`
- `source_url`: 任意。親投稿のみ。SNS本文に載せる掲示板一覧アンカーURL。未指定時はリクエスト元から推定

補足:

- 返信では画像、gdgd、SNS転記を扱いません。
- SNS転記が有効な親投稿では、管理設定でONのSNSへ投稿します。
- 画像付き親投稿では、X、Bluesky、Mastodon、Misskey へ画像も添付します。
- SNS本文は `_TWEND_` より前だけを使い、SNSごとの文字数上限に合わせて `..` で省略します。
- SNS本文には `最新はこちら` と掲示板一覧アンカーURLを含めます。フロントエンドは `#post-000000` の仮URLを送信し、API は保存後の投稿IDで `000000` を置換します。

成功レスポンス:

```json
{
  "success": true,
  "message": "..."
}
```

## POST `?action=updatePost`

既存投稿を更新します。投稿時のパスワードが必要です。

Content-Type:

- `multipart/form-data`

フィールド:

- `id`: 必須
- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `file`: 任意。指定時だけ画像を差し替え。既存画像は上書きせず履歴名へ退避
- `gdgd`: 任意。親投稿のみ
- `tweet_off`: 任意。親投稿のみ。UIでは `SNS転記OFF`

パスワード不一致時は HTTP 403 を返します。

投稿更新は掲示板内だけに反映します。親投稿であっても、SNS側の既存投稿は編集せず、再投稿もしません。返信ではSNS転記を扱いません。

## POST `?action=deletePost`

投稿をソフトデリートします。投稿時のパスワードが必要です。

Content-Type:

- `multipart/form-data`

フィールド:

- `id`: 必須
- `password`: 必須

動作:

- SQLite行は削除せず、`deleted_at` を設定します。
- 親スレッドを削除すると、その返信にも `deleted_at` を設定します。
- 返信を削除する場合、その返信だけが対象です。
- 画像ファイルは削除しません。
- 一覧、スレッド、検索APIでは削除済み投稿を返しません。

## GET `?action=rss`

削除されていない親投稿を新着順に最大30件含む RSS 2.0 XML を返します。

## GET `?action=refreshSocialReactions&admin_password={password}`

管理者向けに、保存済みSNS投稿URL/IDからリアクション数を取得してキャッシュします。

対象は、作成から7日以内の未削除親投稿です。

取得指標:

- X: 閲覧数、いいね数、リポスト数
- Bluesky: Like数、Repost数、Quote数
- Mastodon: Boost数、Favorite数
- Misskey: リアクション分類

## GET `?action=cronRefreshSocialReactions&api_key={key}`

外部定期実行向けのSNSリアクション更新エンドポイントです。

APIキーは管理画面に表示される `cronApiKey` を使います。GitHub Actions や外部HTTPスケジューラから呼び出す想定です。

対象は `refreshSocialReactions` と同じく、作成から7日以内の未削除親投稿です。

ローカルサーバーのCronを使う場合は、管理画面に表示される `server/cron.php` のファイルパスをCron設定に登録します。

## GET `?action=listDeletedPosts&admin_password={password}`

管理者向けに削除済み投稿を返します。`admin_password` は `DOTEITA_ADMIN_PASSWORD` 環境変数と一致する必要があります。

## GET `?action=listAnalyticsPosts&admin_password={password}`

アナリティクス画面向けに、未削除の親投稿をすべて返します。レスポンスは通常の `Post` 形式で、キャッシュ済みSNSリアクション数も含みます。

## POST `?action=restorePost`

管理者向けに削除済み投稿を復元します。

フィールド:

- `id`: 必須
- `admin_password`: 必須

## Post 型

```ts
type Post = {
  id: number;
  display_no?: number;
  reply_no?: number;
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
  social_links?: {
    x?: string | null;
    bluesky?: string | null;
    mastodon?: string | null;
    misskey?: string | null;
  };
  social_reactions?: {
    x?: { likes: number; reposts: number; impressions: number };
    bluesky?: { likes: number; reposts: number; quotes: number };
    mastodon?: { boosts: number; favs: number };
    misskey?: {
      fire: number;
      eyes: number;
      cry: number;
      thinking: number;
      party: number;
      other: number;
    };
  };
  replies?: Post[];
  reply_count?: number;
};
```

`password_hash` は API レスポンスに含めません。

## Laravel API

`server/laravel/routes/api.php` に Laravel 版のルートがありますが、現在のフロントエンドの標準接続先ではありません。Laravel API はまだ本番仕様扱いではありません。

## Web APIではない移行操作

ローカルアーカイブ `LOG_*.cgi` インポートは、Web API と管理画面からは公開しません。ローカル運用バッチまたはPHPコマンドから `server/db.php` の `importLocalArchiveDirectory()` を直接呼び出します。
