# DB / ランタイムデータ

[English DB notes](../DB.md)

ThreadForge は共通の PHP バックエンドを使いますが、DB とアップロードファイルはフロントごとに独立させます。

## 開発環境

開発中は `THREADFORGE_FRONTEND_ID` ごとに次の場所へ保存します。

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

現在のフロントID:

- `image-board`
- `file-uploader`
- `guide-posts`
- `proxy-release`
- `materials-library`

5つのフロントは、投稿、設定、セッション、ユーザー、アップロードファイルを共有しません。

## リリースZip

リリースZipはフロント単体のソフトとして作ります。展開後は、その単体アプリ内に保存します。

```text
database.sqlite
storage/data/
```

つまり `file-uploader` のZipと `image-board` のZipは別アプリで、それぞれ自分の `database.sqlite` を持ちます。

## フロント別DBドキュメント

- [image-board DB](frontends/image-board/DB.md)
- [file-uploader DB](frontends/file-uploader/DB.md)
- [guide-posts DB](frontends/guide-posts/DB.md)
- [proxy-release DB](frontends/proxy-release/DB.md)
- [materials-library DB](frontends/materials-library/DB.md)

## 環境変数

- `THREADFORGE_FRONTEND_ID`: 開発中のランタイム保存先を選ぶ
- `THREADFORGE_DB_FILE`: SQLiteファイルの完全なパス
- `THREADFORGE_STORAGE_DIR`: アップロードファイル保存ディレクトリ
- `THREADFORGE_STORAGE_PUBLIC_BASE`: 保存ファイル公開URLの接頭辞

## クリーン初期化

本当に初期化したいときだけ使います。

開発環境:

```powershell
Remove-Item server\runtime\<frontend-id>\database.sqlite
Get-ChildItem server\runtime\<frontend-id>\storage\data -File | Remove-Item
```

リリース配置先:

```powershell
Remove-Item database.sqlite
Get-ChildItem storage\data -File | Remove-Item
```
