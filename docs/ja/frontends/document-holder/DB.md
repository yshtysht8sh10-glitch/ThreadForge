# document-holder DB

[DB一覧へ戻る](../../DB.md)

フロントエンドID: `document-holder`

## ランタイムファイル

開発環境:

```text
server/runtime/document-holder/database.sqlite
server/runtime/document-holder/storage/data/
```

リリースZIP配置後:

```text
database.sqlite
storage/data/
```

## 利用するテーブル

`document-holder` は `server/db.php` が初期化する共通SQLiteスキーマを使います。

- `material_items`: 記事を1件1行で保存します。
- `material_tags`: 第一階層タグと第二階層タグを保存します。第二階層タグは `parent_id` で第一階層にぶら下がります。
- `material_terms`: 転載、引用、改変などの利用条件を保存します。
- `users`: ログインユーザーの作者名、既定利用規約、既定の目次表示を保存します。
- `settings`: タイトル、説明文、説明バナー、デザイン設定を保存します。

## 記事データ

- `archive_path` は `index.html` を含む記事ZIPを指します。
- `notes` は画面表示用のHTML本文または概要を保存します。
- `draft` が `1` の記事は「書きかけ」として表示します。
- `view_count` は記事閲覧数です。記事詳細を開いたときに加算します。

## レガシー移行

`tools/migrate_document_holder_runtime.php` は旧 `04_DMF` のHTMLを読み、画像参照を表示可能な形へ整え、記事ZIPを生成して `storage/data/` に保存します。

移行時は旧カテゴリを「指南」の第二階層タグに割り当て、記事日時は元HTMLファイルの更新日時を使います。

## 分離ルール

`document-holder` は他フロントエンドのDBやストレージを読み書きしません。
