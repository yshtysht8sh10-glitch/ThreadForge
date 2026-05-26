# ThreadForge ツール

[English tools guide](README.md)

このディレクトリには、配布物の作成やサイト運営者向けの運用スクリプトをまとめています。

## 配布用Zip

配布用アーカイブを作成します。

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

作成されたZipは `release/threadforge-<version>.zip` に出力されます。

配布用Zipは、レンタルサーバーへそのまま配置しやすい形で作成します。展開した `threadforge-<version>` ディレクトリの中身を公開ディレクトリへアップロードしてください。

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

フロントエンドは同じサイト上の `./api.php` を呼び出す前提でビルドされます。配布用Zipには、ビルド済みフロントエンド、PHPバックエンド、ドキュメントを含めます。運用スクリプト自体は配布用Zipに含めません。次の実行時データは意図的に含めません。

- `server/database.sqlite`
- `server/storage/data/` 配下のアップロード画像
- ローカルPHPバイナリ
- 依存パッケージディレクトリ
- ログ
- 旧環境からのインポート元データ

初回アクセス時、PHPから配置先ディレクトリへ書き込める状態であればSQLite DBが自動作成されます。画像アップロードのため、`storage/data/` にも書き込み権限を付けてください。

## ローカルアーカイブインポート

ローカルアーカイブログを現在のSQLite DBへ取り込みます。

```powershell
tools\import_local_archive.bat legacy\data
```

指定したディレクトリから `LOG_*.cgi` と参照画像を読み込みます。このインポートは非破壊です。既存の投稿、画像、設定を削除せず、再実行時は名前、本文、日時が一致する取り込み済み投稿と返信をスキップします。

## 複数アーカイブフォルダの一括インポート

`legacy/import_data` のような複数フォルダ構成の過去ログを、親投稿の投稿日時順に取り込みます。

```powershell
tools\import_threadforge_archives.bat legacy\import_data
```

レンタルサーバーのcronから実行する場合は、標準出力が見えないことがあるため、ログ出力つきの専用PHPを指定します。

```text
/home/users/0/main.jp-mugendoteita/web/DotoEita/01_threadforge/tools/import_threadforge_archives_cron.php
```

実行結果は ThreadForge 直下の `import_threadforge_archives.log` に出力されます。
実行後はcronを無効化し、必要に応じて `tools/` を公開ディレクトリ外へ移動してください。

フォルダごとの判定:

- `bbs1-999`: ログ内の画像名、内部フィールド、タイトルから通常投稿/特殊投稿を自動判定します。
- `bbs10_DoteitaArchive_Doteita`: 通常投稿として取り込みます。
- `bbs20_DoteitaArchive_gdgd`: 特殊投稿として取り込みます。
- `bbsOO_DoteitaArchive`: 投稿タイトルに `gdgd` または `ｇｄｇｄ` が含まれる場合だけ特殊投稿として取り込みます。

実行前には、管理画面の保守からフルバックアップZIPをエクスポートしてください。
