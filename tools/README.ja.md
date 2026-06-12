# ThreadForge 運用ツール

[English tools guide](README.md)

## フロントエンドごとのDB分離

バックエンドコードは共通ですが、DBとアップロードファイルは `THREADFORGE_FRONTEND_ID` ごとに分かれます。

例:

```text
server/runtime/image-board/database.sqlite
server/runtime/image-board/storage/data/
server/runtime/file-uploader/database.sqlite
server/runtime/file-uploader/storage/data/
```

複数フロントを動かす場合は、各フロント/APIごとに別の `THREADFORGE_FRONTEND_ID` を設定してください。

配布、インポート、修復など、サイト運用者向けのスクリプトをまとめています。

## 配布 Zip

配布用 Zip を作成します。

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

出力先:

```text
release/threadforge-<frontend-id>-<version>.zip
```

Zip を展開し、image-board の `11_image_board/` または file-uploader の `12_file_uploader/` をレンタルサーバーの `/DotoEita/` へアップロードします。

配布 Zip には、ビルド済みフロントエンド、PHP バックエンド、ドキュメントを含めます。DB、画像、ログ、ローカル運用ツール、旧ログデータは含めません。

## ローカルアーカイブログのインポート

単一フォルダの `LOG_*.cgi` と画像を現在の SQLite DB に取り込みます。

```powershell
tools\import_local_archive.bat legacy\data
```

インポートは非破壊です。既存の投稿、画像、設定は削除しません。

## 複数アーカイブフォルダの一括インポート

`legacy/import_data` のような複数フォルダ構成を、親投稿の投稿日時順に取り込みます。

```powershell
tools\import_threadforge_archives.bat legacy\import_data
```

フォルダごとの判定:

- `bbs1-999`: ログ内の情報から通常投稿/特殊投稿を判定します。
- `bbs10_DoteitaArchive_Doteita`: 通常投稿として取り込みます。
- `bbs20_DoteitaArchive_gdgd`: 特殊投稿として取り込みます。
- `bbsOO_DoteitaArchive`: タイトルに `gdgd` または旧 gdgd 表記がある場合だけ特殊投稿として取り込みます。

レンタルサーバーの cron から実行する場合は次を指定します。

```text
/path/to/threadforge/tools/import_threadforge_archives_cron.php
```

結果は ThreadForge 直下の `import_threadforge_archives.log` に出力されます。

## インポート画像修復

採番しなおしなどで本文やコメントと画像がずれた場合、旧ログを照合して画像だけを貼り直します。投稿本文やコメントは再インポートしません。

```powershell
tools\repair_imported_images.bat legacy\import_data
tools\repair_imported_images.bat legacy\import_data --apply --offset=0 --limit=200
```

1 行目はドライランです。実際に画像をコピーし、DB の `image_path` を更新する場合は `--apply` を付けます。

レンタルサーバーでは次をブラウザまたは cron から実行できます。

```text
https://example.com/threadforge/tools/repair_imported_images_cron.php
https://example.com/threadforge/tools/repair_imported_images_cron.php?reset=1&limit=100
https://example.com/threadforge/tools/repair_imported_images_cron.php?archive=legacy/import_data
```

結果と変更内容は ThreadForge 直下の `repair_imported_images.log` に残ります。

## 最新インポート投稿の差分更新

BBSNote 側で最新の投稿だけ更新された場合、ThreadForge の投稿番号を変えずに、最新 N 件だけ本文、タイトル、画像、未登録返信を反映できます。

```powershell
tools\update_imported_recent.bat legacy\import_data --limit=10
tools\update_imported_recent.bat legacy\import_data --limit=10 --apply
tools\update_imported_recent.bat legacy\import_data --limit=10 --apply --add
```

1 行目はドライランです。`--apply` で一致した既存投稿を更新します。`--add` を付けると、未一致の最新アーカイブ投稿を新規投稿として追加します。

レンタルサーバーでは次を使います。

```text
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10&apply=1
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10&apply=1&add=1
```

`apply=1` がない場合は確認のみです。`add=1` がない場合、未一致の投稿は `new_candidates` としてログに出るだけで追加されません。

結果は ThreadForge 直下の `update_imported_recent.log` に残ります。

## 注意

- 実行前に管理画面のフルバックアップを取得してください。
- レンタルサーバーで長時間処理になる場合は、`limit` と `offset` を使って分割実行してください。
- 画像修復と差分更新は旧ログとの照合に依存します。旧ログを上書きする場合は、必要な画像と `LOG_*.cgi` が揃っていることを確認してください。
