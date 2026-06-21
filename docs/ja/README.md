# ThreadForge ドキュメント

人間が読むためのドキュメントは、日本語/英語を切り替えられるHTMLとして生成します。Markdown は保守用の原稿です。

HTMLを再生成する場合:

```powershell
npm run build:docs
```

## 共通ドキュメント

- `SPEC.md`: 仕様索引と共通仕様
- `DB.md`: DB/runtime データの索引と運用ルール
- `API.md`: PHP API のアクションとリクエスト/レスポンス
- `ARCHITECTURE.md`: フロントエンド/バックエンド構成
- `FRONTEND_ARCHITECTURE.md`: 複数フロントエンド構成
- `MIGRATION.md`: ローカルアーカイブログ取り込みメモ
- `TESTING.md`: テストコマンドと確認範囲
- `../../CHANGELOG.md`: 英語変更履歴
- `../../CHANGELOG.ja.md`: 日本語変更履歴

## フロントエンド別ドキュメント

- `frontends/image-board/README.md`
- `frontends/image-board/DB.md`
- `frontends/image-board/SPEC.md`
- `frontends/file-uploader/README.md`
- `frontends/file-uploader/DB.md`
- `frontends/file-uploader/SPEC.md`
- `frontends/guide-posts/README.md`
- `frontends/guide-posts/DB.md`
- `frontends/guide-posts/SPEC.md`
- `frontends/proxy-release/README.md`
- `frontends/proxy-release/DB.md`
- `frontends/proxy-release/SPEC.md`
- `frontends/materials-library/README.md`
- `frontends/materials-library/DB.md`
- `frontends/materials-library/SPEC.md`

## リリースZip

```powershell
npm run release:image-board
npm run release:file-uploader
npm run release:proxy-release
npm run release:materials-library
```

リリースZipは `release/threadforge-<frontend-id>-<version>.zip` に作られます。

- image-board: `11_image_board/`
- file-uploader: `12_file_uploader/`
- materials-library: `15_materials_library/`
- その他のフロント: frontend id の `-` を `_` に置き換えたディレクトリ

開発環境では `server/runtime/<frontend-id>/...`、配置後は各公開用ディレクトリ内の `database.sqlite` と `storage/data/` を使います。リリースZipには実運用のDBとアップロード済みファイルは含めません。
