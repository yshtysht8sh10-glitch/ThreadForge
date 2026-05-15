# アーキテクチャ

[English architecture notes](../ARCHITECTURE.md)

## 現在の構成

```text
client/ React + TypeScript + Vite
  |
  | HTTP JSON / multipart form-data
  v
server/api.php
  |
  +-- server/db.php
  |     +-- server/database.sqlite
  |
  +-- server/storage/data/
```

## フロントエンド

`client/src/App.tsx` がルーティングを定義します。

- `/`: `HomePage`
- `/thread/:id`: `ThreadPage`
- `/post`: `PostFormPage`
- `/search`: `SearchPage`
- `/edit/:id`: `EditPostPage`

API 呼び出しは `client/src/api.ts` に集約されています。

`VITE_USE_MOCK` が `true` または未指定の場合、フロントエンドはモックレスポンスを使います。実APIを使う場合は `VITE_USE_MOCK=false` を指定します。

## バックエンド

`server/api.php` は `action` パラメータで処理を振り分けます。

主なアクション:

- スレッド一覧取得
- スレッド取得
- 投稿取得
- 検索
- 投稿作成
- 投稿更新
- 投稿削除
- RSS配信
- 管理者向け削除済み投稿一覧/復元
- SNSリアクション更新

DB接続、テーブル作成、画像保存補助、JSONレスポンスは `server/db.php` にあります。

## データの流れ

新規親投稿:

1. フロントエンドが `multipart/form-data` を送信します。
2. API が必須項目を検証します。
3. パスワードをハッシュ化します。
4. SQLite に投稿行を保存し、投稿IDを確定します。
5. 画像があれば、投稿IDを使って `server/storage/data/` に保存します。
6. 画像パスを投稿行に反映します。
7. SNS転記が有効な場合、ON のSNSへ投稿し、転記先ID/URLを保存します。
8. 成功または失敗をJSONで返します。

画像差し替え:

1. フロントエンドが投稿ID、パスワード、差し替え画像を送信します。
2. API がパスワードを検証します。
3. 既存画像が同じ最終ファイル名を使う場合、先に履歴名へ退避します。
4. 新しい画像を `postId.ext` として保存します。
5. 投稿行の `image_path` を更新します。

スレッド表示:

1. フロントエンドが `getThread` を呼びます。
2. API が親投稿を取得します。
3. 同じ `thread_id` の返信を作成日時昇順で取得します。
4. `{ thread, replies }` を返します。

削除:

1. フロントエンドが投稿IDとパスワードを送信します。
2. API がパスワードを検証します。
3. 親投稿削除では、そのスレッドと返信に `deleted_at` を設定します。
4. 返信削除では、その返信だけに `deleted_at` を設定します。
5. DB行と画像ファイルは復元/監査用に残します。

表示:

- 一覧、スレッド、検索APIは `deleted_at IS NULL` の行だけを返します。
- 削除済み投稿は通常UIから見えなくなります。
- 内部データは復元や確認に使える状態で残ります。

## SNS転記

- X、Bluesky、Mastodon、Misskey はデフォルトOFFです。
- 管理画面では X、Bluesky、Mastodon、Misskey の設定グループを分けます。
- 各SNSがOFFの間、そのSNSの認証情報欄は無効化されます。
- 投稿フォームでは、有効なSNSごとに読み取り専用プレビューを表示します。
- `_TWEND_` 以降はSNS転記対象外です。
- `SNS転記OFF` の投稿は外部SNSへ送信しません。
- 画像付き親投稿は、有効な場合 X、Bluesky、Mastodon、Misskey へ画像も送信します。
- 文字数上限を超える場合、送信不可にはせず `..` で省略します。
- 既定上限は X 280、Bluesky 300、Mastodon 500、Misskey 3000 です。
- X は重み付き文字数、その他は通常の文字数でプレビューします。
- SNS本文には掲示板一覧の当該投稿アンカーへ誘導する「最新はこちら」リンクを含めます。
- 親投稿を編集してもSNS側は更新・再投稿しません。返信ではSNS転記を扱いません。
- リアクション更新は管理画面の保守タブ、`server/cron.php`、APIキー付き外部定期実行URLから実行できます。
- 自動リアクション更新は、作成から7日以内の未削除親投稿だけを対象にします。
- 表示指標は X の閲覧数/いいね/リポスト、Bluesky の Like/Repost/Quote、Mastodon の Boost/Favorite、Misskey のリアクション分類です。

## 安全な本文表示

- React の通常描画で HTML タグを文字列として扱います。
- `LinkedText` が URL をリンク化します。

## Laravel

`server/laravel/` は将来の移行候補です。ルート、モデル、コントローラ、マイグレーションはありますが、現在の標準APIではありません。

Laravel への完全移行は未実装です。

## 未実装

- CSRF対策
- 本文とメタデータの編集履歴
