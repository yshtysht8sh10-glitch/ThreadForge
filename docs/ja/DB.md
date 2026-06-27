# DB / ランタイムデータ

[English DB notes](../DB.md)

ThreadForge は共通のPHPバックエンドを使いますが、DBとアップロードファイルはフロントエンドごとに分離します。

開発中は `THREADFORGE_FRONTEND_ID` ごとに次の場所へ保存します。

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

現在のフロントエンドID:

- `image-board`
- `file-uploader`
- `document-holder`
- `proxy-release`
- `materials-library`

5つのフロントエンドは、投稿、設定、セッション、ユーザー、アップロードファイルを共有しません。

## リリースZip

リリースZipはフロントエンド単体のソフトとして作ります。展開後は、その単体アプリ内に保存します。

```text
database.sqlite
storage/data/
```

つまり `file-uploader` のZipと `image-board` のZipは別アプリで、それぞれが自分の `database.sqlite` を持ちます。

## フロントエンド別DBドキュメント

- [image-board DB](frontends/image-board/DB.md)
- [file-uploader DB](frontends/file-uploader/DB.md)
- [document-holder DB](frontends/document-holder/DB.md)
- [proxy-release DB](frontends/proxy-release/DB.md)
- [materials-library DB](frontends/materials-library/DB.md)

## 主な環境変数

- `THREADFORGE_FRONTEND_ID`: 開発中のランタイム保存先を選ぶ
- `THREADFORGE_DB_FILE`: SQLiteファイルの完全なパス
- `THREADFORGE_STORAGE_DIR`: アップロードファイル保存ディレクトリ
- `THREADFORGE_STORAGE_PUBLIC_BASE`: 保存ファイル公開URLの接頭辞

## クリーン初期化

本当に初期化したいときだけ使います。

```powershell
Remove-Item server/runtime/<frontend-id>/database.sqlite
Remove-Item server/runtime/<frontend-id>/storage/data/* -Recurse
```

リリース配置先では、そのアプリ配下の `database.sqlite` と `storage/data/` をバックアップしてから削除してください。
