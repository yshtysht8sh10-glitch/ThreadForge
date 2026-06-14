# 素材庫 DB

フロントエンドIDは `materials-library` です。

## 保存先

開発時:

```text
server/runtime/materials-library/database.sqlite
server/runtime/materials-library/storage/data/
```

配布版:

```text
15_materials_library/database.sqlite
15_materials_library/storage/data/
```

## テーブル

- `material_items`: 素材・画像・作者・タグ・パスワード・論理削除状態
- `material_tags`: 管理者が編集する用途タグ
- `material_terms`: 管理者が編集する利用規約
- `material_item_terms`: 各素材の規約ごとの○×
- `material_media`: 音声・ボイス素材の試聴用MP3
- `users`: 共通ユーザー情報と素材庫用作者名・規約初期値

`user_id` がある素材はID単位、ない素材は作者名単位でまとめます。同名でもIDあり・なしは統合しません。

差し替え前のファイルは履歴名で保持します。論理削除ではファイルを残し、管理者の完全消去で削除します。フルバックアップZipにはSQLite DBと `storage/data/` の全ファイルを含めます。

## 旧素材庫のインポート

`Sozaiko.html`、`zip/` 内の圧縮ファイル、`img/` 内のプレビューを読み込みます。旧フォルダのファイルは移動・削除せず、素材庫ランタイムへコピーします。

```powershell
tools\import_legacy_materials.bat frontends\materials-library\legacy\05_Sozaiko --dry-run
tools\import_legacy_materials.bat frontends\materials-library\legacy\05_Sozaiko
```

各素材には一意な `material_items.legacy_source` を記録するため、同じコマンドを再実行しても二重登録されません。ユーザーIDのない旧素材は作者名単位でまとめます。HTMLのリンク誤記はプレビューと同名の圧縮ファイルがあれば補正し、HTMLに未掲載の圧縮ファイルも補完登録します。

利用規約は legacy の5項目をそのまま使用します。`○` は許可、`×` は不許可、`△` は未確定の `?` として保持します。再インポート時には既存素材の規約回答も legacy と同期します。

```powershell
server\.php\php.exe tools\audit_material_terms.php
```

この監査コマンドは全素材・全回答を `Sozaiko.html` と照合します。
