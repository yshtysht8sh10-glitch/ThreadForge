# DB / ランタイムデータ

[English DB notes](../DB.md)

現在のバックエンドは SQLite とファイルストレージを使います。

## ランタイムファイル

- DB: `server/database.sqlite`
- アップロード済み/インポート済みメディア: `server/storage/data/`

どちらも Git 管理外です。ローカルの実行時データであり、ソースコードではありません。

## 初期化動作

`server/db.php` は API 起動時に SQLite ファイル、テーブル、不足カラム、ストレージディレクトリを作成します。既存の行、画像、設定は削除しません。

開発中にクリーンな掲示板が必要な場合を除き、`server/database.sqlite` と `server/storage/data/` は削除しないでください。

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
  gdgd INTEGER NOT NULL DEFAULT 0,
  tweet_off INTEGER NOT NULL DEFAULT 0,
  tweet_text TEXT,
  tweet_url TEXT,
  tweet_like_count INTEGER NOT NULL DEFAULT 0,
  tweet_retweet_count INTEGER NOT NULL DEFAULT 0,
  tweet_comment_count INTEGER NOT NULL DEFAULT 0,
  tweet_impression_count INTEGER NOT NULL DEFAULT 0,
  bluesky_uri TEXT,
  bluesky_cid TEXT,
  bluesky_url TEXT,
  bluesky_like_count INTEGER NOT NULL DEFAULT 0,
  bluesky_repost_count INTEGER NOT NULL DEFAULT 0,
  bluesky_quote_count INTEGER NOT NULL DEFAULT 0,
  mastodon_id TEXT,
  mastodon_url TEXT,
  mastodon_boost_count INTEGER NOT NULL DEFAULT 0,
  mastodon_fav_count INTEGER NOT NULL DEFAULT 0,
  misskey_id TEXT,
  misskey_url TEXT,
  misskey_fire_count INTEGER NOT NULL DEFAULT 0,
  misskey_eyes_count INTEGER NOT NULL DEFAULT 0,
  misskey_cry_count INTEGER NOT NULL DEFAULT 0,
  misskey_thinking_count INTEGER NOT NULL DEFAULT 0,
  misskey_party_count INTEGER NOT NULL DEFAULT 0,
  misskey_other_count INTEGER NOT NULL DEFAULT 0
)
```

SNS関連カラムは、各プラットフォームの転記先ID/URLとキャッシュ済みリアクション数を保存します。X は互換性のため `tweet_*` カラム名を維持しています。

## settings テーブル

```sql
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL
)
```

設定は JSON セクションとして保存します。

- `config`
- `skin`
- `security`

`security` セクションには `adminPasswordHash` と `cronApiKey` を保存します。`cronApiKey` は未設定時に自動生成され、GitHub Actions など外部スケジューラ向けの `cronRefreshSocialReactions` API で使います。

## バックアップ

管理画面のエクスポートボタンで、次を含む JSON ファイルをダウンロードできます。

- DB行
- base64 化した画像
- 管理設定

バックアップJSONのインポートは完全復元です。投稿と画像を置き換えたうえで、バックアップ内の設定を復元します。

## ローカルアーカイブインポート

ローカルアーカイブログのインポートは、Web管理画面ではなくローカル運用バッチまたはPHPコマンドから行います。個別環境用のバッチファイルは、明示的に配布対象にすると決めない限りコミットしません。

インポータは標準でルートの `data/` から`LOG_*.cgi` を読み、参照画像を `server/storage/data/` へコピーします。

このインポートは非破壊です。

- 既存投稿を削除しません。
- 既存画像を削除しません。
- 管理設定をリセットしません。
- 再実行時は、名前、本文、時刻が一致する既存投稿/返信をスキップします。

インポートされた投稿には未知の生成済みパスワードハッシュを使います。必要に応じて管理画面から管理します。

## クリーン初期化

公開前やローカル掲示板を空にしたい場合:

1. 必要なら先にバックアップをエクスポートします。
2. PHPサーバーを停止します。
3. `server/database.sqlite` を削除します。
4. `server/storage/data/` 配下のファイルを削除します。ディレクトリは残すか、後で再作成します。
5. PHPサーバーを再起動します。

次の API リクエスト時にDBスキーマとストレージディレクトリが再作成されます。

PowerShell例:

```powershell
Remove-Item server\database.sqlite
Get-ChildItem server\storage\data -File | Remove-Item
```

本当にランタイムデータをリセットしたい場合だけ実行してください。
