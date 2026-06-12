# ThreadForge ドキュメント

人が読むドキュメントは、日本語/Englishを画面上で切り替えられるHTMLとして生成します。[`../index.html`](../index.html) を開いてください。

Markdownファイルは更新しやすい原稿として残します。編集後は次のコマンドでHTMLを再生成します。

```powershell
npm run build:docs
```

このディレクトリは日本語ドキュメントの索引です。

## 共通ドキュメント

- `SPEC.md`: 仕様索引と共通契約
- `DB.md`: DB/ランタイムデータの索引と共通ルール
- `API.md`: PHP API のアクションとリクエスト/レスポンス
- `ARCHITECTURE.md`: フロントエンド/バックエンド構成
- `FRONTEND_ARCHITECTURE.md`: 複数フロント構成と移行方針
- `MIGRATION.md`: ローカルアーカイブログの取り込みメモ
- `TESTING.md`: テストコマンドと確認範囲
- `../../CHANGELOG.md`: 英語変更履歴
- `../../CHANGELOG.ja.md`: 日本語変更履歴

## フロント別ドキュメント

各フロントは README、DB、SPEC を持ちます。

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

## 運用ツール

リリースZip作成やローカルアーカイブ取り込み用のスクリプトは `tools/` にあります。

```powershell
npm run release:image-board
npm run release:file-uploader
```

リリースZipは `release/threadforge-<frontend-id>-<version>.zip` に作られます。開発環境では `server/runtime/<frontend-id>/...`、配置後は `database.sqlite` と `storage/data/` を使います。
