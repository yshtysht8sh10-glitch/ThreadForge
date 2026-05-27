# ThreadForge

[English README](README.md)

ThreadForge は、投稿、画像、返信、モデレーション、コミュニティアーカイブを扱う軽量なスレッド掲示板エンジンです。投稿、画像、返信、モデレーション、コミュニティアーカイブを扱う新しいセルフホスト型掲示板として設計しています。

## リポジトリ構成

- `client/`: React、TypeScript、Vite のフロントエンド
- `server/`: PHP API、SQLite ストレージ、PHPUnit テスト
- `docs/`: アーキテクチャ、API、DB、移行、テスト関連ドキュメント
- `docs/SPEC.md`: 現在の製品仕様
- `docs/ja/SPEC.md`: 日本語版の製品仕様
- `tools/`: 配布と運用のスクリプト

ローカルアーカイブファイルや過去ログ画像は、標準では Git 管理外です。必要な場合だけローカルで取り込めますが、アプリの通常動作には不要です。

## ローカル起動

フロントエンド:

```powershell
cd client
copy .env.example .env
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```


バックエンド:

```powershell
cd server
composer install
php -S 127.0.0.1:8000 -t .
```

初回はブラウザの管理画面で管理者パスワードを設定してください。復旧や自動セットアップ用には `THREADFORGE_ADMIN_PASSWORD` も使えます。

起動後、次の URL を開きます。

```text
http://127.0.0.1:5173
```

## テスト

フロントエンド:

```powershell
cd client
npm test -- --run
npm run build
```

バックエンド:

```powershell
cd server
vendor/bin/phpunit
```

## バージョン管理

バージョンは次のファイルで管理します。

- `VERSION`
- `CHANGELOG.md`
- `client/package.json`
- `client/src/version.ts`

セマンティックバージョニングを使い、バージョン番号と変更履歴は同じコミットで更新します。

## ランタイムデータ

バックエンドは次のローカルファイルを作成します。

- `server/database.sqlite`
- `server/storage/data/*`

これらは Git 管理外です。環境間でデータを移す場合は、保守画面のフルバックアップZIP インポート/エクスポート機能を使います。フルバックアップZIPにはSQLite DB本体と画像が含まれます。

## レンタルサーバーへの設置

配布Zipを展開し、`threadforge-<version>` ディレクトリの中身をレンタルサーバーの公開ディレクトリへアップロードします。

```text
threadforge-<version>/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

フロントエンドは同じサイト上の `./api.php` を呼び出します。実行時DBとアップロード画像はZipに含めません。初回アクセス時、PHPから配置先へ書き込める状態であればSQLite DBが自動作成されます。

`storage/data/` は画像アップロード先です。必要に応じて、レンタルサーバー側で書き込み権限を設定してください。

DBがない初回起動時は、掲示板デザインは標準の黒基調、特殊投稿OFF、一覧の並び順はNo順、SNS投稿ハッシュタグ `#art`、SNS連携すべてOFFかつ認証情報空欄で開始します。一覧画面では年/月を複数選択して表示対象を絞り込めます。

## SNS転記の運用

X、Bluesky、Mastodon、Misskey 連携はデフォルト OFF です。この状態では外部 API を呼ばず、投稿は掲示板内だけに保存されます。各 SNS は管理画面で個別の設定グループを持ち、OFF の間は認証情報の入力欄も無効化されます。

SNS転記が有効な場合、新規の親投稿作成時に ON の SNS へ転記します。画像が添付されている場合、X、Bluesky、Mastodon、Misskey へ画像も送信します。SNS本文には「最新はこちら」と掲示板一覧の当該投稿アンカー URL を含め、文字数上限を超える場合は送信を止めず `..` で省略します。

投稿編集は掲示板内だけに反映し、SNS側の既存投稿は編集・再投稿しません。SNSリアクション数は管理画面から手動更新できるほか、`server/cron.php` または管理画面に表示される APIキー付きURLから自動更新できます。自動更新の対象は、SNS投稿IDを持つ未削除の親投稿すべてです。

## ドキュメント

- `docs/SPEC.md`: 英語版の現在仕様
- `docs/ja/SPEC.md`: 日本語版の現在仕様
- `CHANGELOG.md`: 英語版 変更履歴
- `CHANGELOG.ja.md`: 日本語版 変更履歴
- `docs/README.md`: 英語版ドキュメント索引
- `docs/ja/README.md`: 日本語版ドキュメント索引
- `docs/API.md`: 英語版 API リファレンス
- `docs/ja/API.md`: 日本語版 API リファレンス
- `docs/DB.md`: 英語版 DB/ランタイムデータメモ
- `docs/ja/DB.md`: 日本語版 DB/ランタイムデータメモ
- `docs/MIGRATION.md`: 英語版 移行メモ
- `docs/ja/MIGRATION.md`: 日本語版 移行メモ
- `docs/ARCHITECTURE.md`: 英語版 アーキテクチャメモ
- `docs/ja/ARCHITECTURE.md`: 日本語版 アーキテクチャメモ
- `docs/TESTING.md`: 英語版 テスト方針
- `docs/ja/TESTING.md`: 日本語版 テスト方針
