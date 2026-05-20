# ThreadForge ツール

[English tools guide](README.md)

このディレクトリには、保守担当者やサイト運営者向けの運用スクリプトをまとめています。

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

フロントエンドは同じサイト上の `./api.php` を呼び出す設定でビルドされます。配布用Zipには、ビルド済みフロントエンド、PHPバックエンド、ドキュメントを含めます。運用スクリプト自体は配布Zipに含めません。次の実行時データは意図的に含めません。

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
