# DotEitaTypeScript 現行仕様

この仕様書は、過去の計画ではなく現在の実装を基準にした仕様です。未実装の機能は、実装済みの仕様に混ぜず「未実装」として明示します。

## 1. 概要

DotEitaTypeScript は、画像投稿掲示板 DotEita を React + TypeScript のフロントエンドと PHP + SQLite の API で再構成するプロジェクトです。

現在の主要実装は次の構成です。

- `client/`: React 18 + TypeScript + Vite の SPA
- `server/api.php`: 現行の PHP API エントリポイント
- `server/db.php`: SQLite 接続、テーブル初期化、画像保存補助関数
- `server/database.sqlite`: 実行時に生成される SQLite DB
- `server/storage/data/`: 投稿画像の保存先
- `server/laravel/`: Laravel 移行用スケルトン。現行本番 API ではない
- `legacy/`: 旧 Perl CGI の参照用ファイル

## 2. 実装済み機能

### フロントエンド

React Router による SPA として、次の画面を持ちます。

- `/`: スレッド一覧
- `/thread/:id`: スレッド詳細、返信一覧、返信投稿フォーム
- `/post`: 新規スレッド投稿フォーム
- `/search`: キーワード検索フォームと検索結果
- `/edit/:id`: 投稿編集、投稿削除フォーム
- `/admin`: 削除済み投稿の確認と復元

フロントエンドは `client/src/api.ts` の API クライアントを通してバックエンドを呼び出します。

`VITE_USE_MOCK` が未指定または `true` の場合はモック API を使います。実 API を使う場合は `VITE_USE_MOCK=false` を指定します。

API のベース URL は `VITE_API_BASE_URL` で指定できます。未指定時は `/api.php` です。

### バックエンド

現行バックエンドは `server/api.php` です。HTTP メソッドと `action` クエリまたは POST フィールドで処理を分岐します。

実装済み action:

- `listThreads`: 親投稿一覧を新着順で最大 50 件返す
- `getThread`: 指定スレッドの親投稿と返信一覧を返す
- `getPost`: 指定投稿を 1 件返す
- `search`: タイトル、本文、投稿者名を部分一致検索する
- `rss`: RSS フィードを返す
- `createPost`: 新規スレッドまたは返信を作成する
- `updatePost`: パスワード一致時に投稿を更新する
- `deletePost`: パスワード一致時に投稿を削除する
- `listDeletedPosts`: 管理者パスワード一致時に削除済み投稿を返す
- `restorePost`: 管理者パスワード一致時にソフト削除済み投稿を復元する

すべての API レスポンスは JSON です。CORS は `Access-Control-Allow-Origin: *` で許可されています。

### 投稿

投稿は SQLite の `posts` テーブルに保存します。

投稿データ:

- `id`: 投稿 ID
- `thread_id`: 所属スレッド ID。親投稿では自身の `id`
- `parent_id`: 親投稿 ID。親投稿では `0`
- `name`: 投稿者名
- `url`: 投稿者 URL / HOME
- `title`: タイトル
- `message`: 本文
- `image_path`: 画像パス。画像なしの場合は `null`
- `password_hash`: 編集、削除用パスワードのハッシュ
- `created_at`: 作成日時。`Asia/Tokyo` の `Y-m-d H:i:s`
- `deleted_at`: ソフト削除日時。未削除の場合は `null`
- `tweet_off`: Tweet 機能を無効化するフラグ
- `tweet_text`: 自動生成された Tweet 文言
- `tweet_url`: 投稿後の Tweet URL
- `tweet_like_count`: いいね数
- `tweet_retweet_count`: リツイート数
- `tweet_comment_count`: コメント数
- `tweet_impression_count`: インプレッション数

新規スレッド投稿では `thread_id=0` で挿入した後、採番された `id` を `thread_id` に反映します。

返信投稿では `thread_id` に対象スレッド ID、`parent_id` に親投稿 ID を指定します。現行 UI では返信の `parent_id` はスレッド親投稿 ID です。

### 画像

投稿と編集時に画像ファイルを添付できます。

実装済みの制約:

- 許可 MIME type は `image/png`, `image/jpeg`, `image/gif`
- 保存先は `server/storage/data/`
- 現行画像の保存ファイル名は投稿 ID と MIME type 由来の拡張子を組み合わせる。例: `123.png`, `123.jpg`, `123.gif`
- 同じ投稿で画像を差し替える場合、旧画像は `123_YYYYMMDDHHMMSS.png` のような履歴名へ退避して保持する
- API 応答では画像パスを `/storage/data/{filename}` として返す
- 投稿更新時に新しい画像が添付された場合、API が参照する `image_path` は最新画像に更新する
- 投稿削除時も画像ファイルは削除せず保持する

### 編集と削除

投稿作成時のパスワードは `password_hash()` で保存されます。編集と削除では `password_verify()` で照合します。

編集対象:

- 投稿者名
- タイトル
- 本文
- 画像。新しい画像が指定された場合のみ差し替え、旧画像は履歴ファイルとして保持

削除仕様:

- 削除は物理削除ではなく `deleted_at` を設定するソフト削除
- スレッド親投稿を削除した場合、そのスレッドの返信にも `deleted_at` を設定する
- 返信を削除した場合、その返信のみに `deleted_at` を設定する
- ソフト削除済み投稿は一覧、スレッド詳細、検索、編集対象に出さない
- DB レコード、投稿本文、パスワードハッシュ、画像パス、画像ファイルは内部に残す

### 検索

`search` は `title`, `message`, `name` を対象に SQLite の `LIKE` で部分一致検索します。

空文字検索では空配列を返します。検索結果は新着順です。`page` と `limit` によるページングに対応し、`limit` は最大 100 件です。

### Tweet

投稿フォームと返信フォームでは Tweet 文言を自動生成します。

- 形式は `[DT000000：タイトル]`、`作者：名前`、本文、元 URL、`#ドット絵 #pixelart`
- 本文中の `_TWEND_` 以降は Tweet 対象外
- URL は Tweet 文字数計算で 23 文字として扱う
- 280 文字を超える場合は本文側を省略し、末尾に `...` を付ける
- Tweet OFF の場合は Tweet 文言を保存しない
- 編集画面では Tweet URL、いいね数、リツイート数、コメント数、インプレッション数を更新できる

### URL / HOME と自動リンク

投稿者 URL / HOME を保存し、スレッド詳細で表示します。

本文中の `http://`, `https://`, `www.` から始まる文字列は画面表示時にリンク化します。HTML タグは React の通常描画により文字列として表示され、HTML として実行されません。

### RSS

`rss` action は削除されていない親投稿を新着順に最大 30 件返します。

### 重複投稿対策

同じ投稿者名、タイトル、本文の投稿が 60 秒以内にある場合、新規投稿を拒否します。

### 管理

`DOTEITA_ADMIN_PASSWORD` 環境変数に設定したパスワードで管理者操作を行います。

- 削除済み投稿一覧を表示する
- ソフト削除済み投稿を復元する
- スレッド親投稿を復元した場合、そのスレッドの返信も復元する

## 3. API 仕様

現行 API は `server/api.php` に集約されています。

### `GET /server/api.php?action=listThreads`

親投稿一覧を返します。

レスポンス:

```json
[
  {
    "id": 1,
    "thread_id": 1,
    "parent_id": 0,
    "name": "name",
    "url": null,
    "title": "title",
    "message": "message",
    "image_path": null,
    "created_at": "2026-05-04 12:00:00",
    "deleted_at": null,
    "tweet_off": false,
    "tweet_text": null,
    "tweet_url": null,
    "tweet_like_count": 0,
    "tweet_retweet_count": 0,
    "tweet_comment_count": 0,
    "tweet_impression_count": 0
  }
]
```

### `GET /server/api.php?action=getThread&id={id}`

指定スレッドを返します。

レスポンス:

```json
{
  "thread": {},
  "replies": []
}
```

存在しない場合は HTTP 404 と `{ "success": false, "message": "..." }` を返します。

### `GET /server/api.php?action=getPost&id={id}`

指定投稿を 1 件返します。

### `GET /server/api.php?action=search&q={query}`

検索結果を `Post[]` で返します。

### `POST /server/api.php?action=createPost`

`multipart/form-data` で投稿を作成します。

フィールド:

- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `thread_id`: 任意。新規スレッドは `0` または未指定
- `parent_id`: 任意。新規スレッドは `0` または未指定
- `file`: 任意
- `tweet_off`: 任意
- `tweet_url`: 任意
- `tweet_like_count`: 任意
- `tweet_retweet_count`: 任意
- `tweet_comment_count`: 任意
- `tweet_impression_count`: 任意

レスポンス:

```json
{
  "success": true,
  "message": "..."
}
```

### `POST /server/api.php?action=updatePost`

`multipart/form-data` で投稿を更新します。

フィールド:

- `id`: 必須
- `name`: 必須
- `url`: 任意
- `title`: 必須
- `message`: 必須
- `password`: 必須
- `file`: 任意
- `tweet_off`: 任意
- `tweet_url`: 任意
- `tweet_like_count`: 任意
- `tweet_retweet_count`: 任意
- `tweet_comment_count`: 任意
- `tweet_impression_count`: 任意

### `POST /server/api.php?action=deletePost`

`multipart/form-data` で投稿を削除します。

フィールド:

- `id`: 必須
- `password`: 必須

動作:

- レコードは削除せず、`deleted_at` に削除日時を保存します
- スレッド親投稿の場合、同じ `thread_id` の投稿にも同じ削除日時を保存します
- 画像ファイルは削除しません

### `GET /server/api.php?action=rss`

RSS 2.0 XML を返します。

### `GET /server/api.php?action=listDeletedPosts&admin_password={password}`

管理者向けにソフト削除済み投稿を返します。

### `POST /server/api.php?action=restorePost`

管理者向けにソフト削除済み投稿を復元します。

フィールド:

- `id`: 必須
- `admin_password`: 必須

## 4. 未実装機能

次の機能は現行実装には含まれていません。

- CSRF 対策
- 認証付き API
- 投稿本文とメタデータの編集履歴
- 返信の多段ネスト表示
- 既存 Perl CGI データの移行ツール
- OGP、サイトマップ等の配信機能
- 本番 Laravel API への完全移行

## 5. 明確に不要とした旧 CGI 機能

次の旧 CGI 固有機能は、現行 React/PHP + SQLite 構成では採用しません。

- `sys/count.cgi` などのバイナリ風インデックスファイルのバックアップ、復旧
- `BN_mailcrypt` によるメールアドレス onmouseover 復号。現行仕様ではメール欄を持たない
- Perl CGI スキン生成、複数スキン切替。現行 UI は React コンポーネントで管理する
- Netscape 4 など旧ブラウザ向け JavaScript
- CGI ロックファイル制御。現行 DB 書き込みは SQLite/PDO に寄せる

## 6. Laravel スケルトンの扱い

`server/laravel/` には Laravel 移行用のルート、モデル、コントローラ、マイグレーションが存在します。

ただし現行仕様では、実際にフロントエンドから利用する API は `server/api.php` です。Laravel 側は移行候補または参照実装であり、本番実装としては未完成です。

## 7. テスト

フロントエンド:

- Vitest
- React Testing Library
- `client/src/api.test.ts`
- `client/src/pages/HomePage.test.tsx`

バックエンド:

- PHPUnit
- `server/tests/StorageLayerTest.php`

主に API クライアント、ホーム画面、SQLite 初期化、投稿整形、画像削除補助などを検証します。

## 8. 現行の注意点

- 既存の一部ソースと旧ドキュメントには文字化けした表示文言が残っています。
- `server/api.php` の画像 URL は `/storage/data/{filename}` を返すため、Web サーバ側で `server/storage/data` をその URL に対応させる必要があります。
- `saveUploadedImage()` は PHP の `is_uploaded_file()` に依存するため、通常のユニットテストでは実アップロード成功経路を直接検証しにくいです。
- `client/src/api.ts` はデフォルトでモックモードです。実 API の検証時は環境変数を明示してください。
- 管理機能を使うには `DOTEITA_ADMIN_PASSWORD` 環境変数が必要です。
