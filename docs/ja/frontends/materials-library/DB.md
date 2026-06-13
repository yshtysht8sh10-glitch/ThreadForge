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
- `users`: 共通ユーザー情報と素材庫用作者名・規約初期値

`user_id` がある素材はID単位、ない素材は作者名単位でまとめます。同名でもIDあり・なしは統合しません。

差し替え前のファイルは履歴名で保持します。論理削除ではファイルを残し、管理者の完全消去で削除します。フルバックアップZipにはSQLite DBと `storage/data/` の全ファイルを含めます。
