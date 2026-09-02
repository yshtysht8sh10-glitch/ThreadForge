# proxy-release DB

[DB索引へ戻る](../../DB.md)

フロントID: `proxy-release`

## ランタイムファイル

開発環境:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

リリースZip配置先:

```text
database.sqlite
storage/data/
```

## 現在のスキーマ

現時点では `server/db.php` が初期化する共通 ThreadForge SQLite スキーマを使います。

想定データ:

- 親投稿を代理公開のリリース項目として扱う
- パッケージや画像はこのフロント専用のストレージに保存する
- `config.proxyTrialPlayButtonsEnabled` は個別試遊ボタンの公開表示を制御し、保存済みの試遊URL自体は変更しない
- 必要になればリリース用メタデータの追加を検討する

## 分離ルール

`proxy-release` は他フロントのDBやストレージを読み書きしません。
## テスト公開のライフサイクル

`material_items.publication_type`（`normal` / `test`）と `material_items.visibility`（`public` / `unlisted`）は独立した項目です。既存行は `normal + public` を既定とし、可視性カラム追加時に既存のテスト公開だけを `test + unlisted` へ移行します。新しいテスト公開は `test + unlisted` とし、初回のWebMUGEN登録時に128ビットの乱数を `webmugen_access_key` として保存します。正式公開への昇格は同じ行を `normal + public` に更新し、アクセスキーと不透明なCatalog IDは維持します。この分離により、将来の `normal + unlisted` も公開種別を流用せず表現できます。期限切れデータは直ちに一覧から隠し、不透明なWebMUGEN Catalogエントリの削除成功後にDB、ZIP、生成画像、メディアを削除します。WebMUGEN削除に失敗した場合は、再試行できるようアクセスキーを含む非表示のローカル行を保持します。
