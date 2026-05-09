# アーキテクチャ

## 現行構成

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

`VITE_USE_MOCK` が `true` または未指定の場合、フロントエンドはモック応答を使います。実 API を使う場合は `VITE_USE_MOCK=false` を指定します。

## バックエンド

`server/api.php` は `action` によって次の処理に分岐します。

- 一覧取得
- スレッド取得
- 投稿取得
- 検索
- 投稿作成
- 投稿更新
- 投稿削除
- RSS 配信
- 管理者による削除済み投稿の確認、復元

DB 接続、テーブル作成、画像保存補助、JSON 応答は `server/db.php` にあります。

## データの流れ

新規投稿:

1. フロントエンドが `multipart/form-data` を送信
2. API が必須項目を確認
3. パスワードをハッシュ化
4. SQLite に投稿を保存して投稿 ID を確定
5. 画像があれば投稿 ID をファイル名にして `server/storage/data/` に保存
6. 画像パスを投稿レコードに反映
7. JSON で成功または失敗を返す

画像差し替え:

1. フロントエンドが投稿 ID、パスワード、差し替え画像を送信
2. API がパスワードを検証
3. 同じ投稿 ID と拡張子の現行画像があれば履歴名へ退避
4. 新しい画像を `投稿ID.ext` として保存
5. 投稿レコードの `image_path` を最新画像に更新

スレッド表示:

1. フロントエンドが `getThread` を呼ぶ
2. API が親投稿を取得
3. 同じ `thread_id` の返信を作成日時昇順で取得
4. `{ thread, replies }` を返す

削除:

1. フロントエンドが投稿 ID とパスワードを送信
2. API がパスワードを検証
3. 親投稿なら同一スレッドの返信にも同じ `deleted_at` を設定
4. 返信なら対象投稿だけに `deleted_at` を設定
5. SQLite レコードと画像ファイルは削除せず内部に保持

表示:

- 一覧、スレッド詳細、検索は `deleted_at IS NULL` の投稿だけを返す
- 削除済み投稿は見た目上消える
- 内部データは復旧や監査に使える状態で残る

Tweet:

- 投稿画面でSNS転記文言を自動生成する
- `_TWEND_` 以降は Tweet 対象外
- SNS転記OFF の投稿は転記文言を保存しない
- 編集画面で Tweet URL と統計値を管理する

安全な本文表示:

- React の通常描画で HTML タグを文字列として扱う
- URL だけ `LinkedText` コンポーネントでリンク化する

## Laravel

`server/laravel/` は将来の移行候補です。ルート、モデル、コントローラ、マイグレーションはありますが、現行アプリの標準 API ではありません。

Laravel への完全移行は未実装です。

## 未実装

- 既存 Perl CGI データ移行
- CSRF 対策
- 本文とメタデータの編集履歴
