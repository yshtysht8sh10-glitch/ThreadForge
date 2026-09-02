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

`material_items.publication_type` は `normal` または `test` です。テスト公開は共通の公開IDを使い、正確な `expires_at` と短い `test_memo` を保持します。正式公開時は同じ行を `normal` に更新し、期限とメモを解除します。期限切れデータは直ちに一覧から隠し、WebMUGEN Catalogの削除成功後にDB、ZIP、生成画像、メディアを削除します。WebMUGEN削除に失敗した場合は、再試行できるよう非表示のローカル行を保持します。
