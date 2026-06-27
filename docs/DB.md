# DB / Runtime Data

[Japanese DB notes](ja/DB.md)

ThreadForge uses one common PHP backend, but each frontend owns its own runtime database and uploaded-file storage.

## Development Layout

During local development, runtime data is separated by `THREADFORGE_FRONTEND_ID`:

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

Current frontend ids:

- `image-board`
- `file-uploader`
- `document-holder`
- `proxy-release`
- `materials-library`

The five frontends must not share DB rows, settings, sessions, users, or uploaded files.

## Release Layout

Release ZIPs are single-frontend apps. After extracting one ZIP, that app stores runtime data locally:

```text
database.sqlite
storage/data/
```

This means `file-uploader` release ZIP and `image-board` release ZIP are separate applications. Each deployed app has its own `database.sqlite`.

## Frontend DB Documents

- [image-board DB](frontends/image-board/DB.md)
- [file-uploader DB](frontends/file-uploader/DB.md)
- [document-holder DB](frontends/document-holder/DB.md)
- [proxy-release DB](frontends/proxy-release/DB.md)
- [materials-library DB](frontends/materials-library/DB.md)

## Overrides

Advanced overrides still work when a deployment needs explicit paths:

- `THREADFORGE_FRONTEND_ID`: selects the development runtime directory
- `THREADFORGE_DB_FILE`: exact SQLite file path
- `THREADFORGE_STORAGE_DIR`: exact uploaded-file directory
- `THREADFORGE_STORAGE_PUBLIC_BASE`: public URL prefix for stored files

## Clean Initialization

Use this only when you intentionally want a clean app.

Development:

```powershell
Remove-Item server\runtime\<frontend-id>\database.sqlite
Get-ChildItem server\runtime\<frontend-id>\storage\data -File | Remove-Item
```

Release deployment:

```powershell
Remove-Item database.sqlite
Get-ChildItem storage\data -File | Remove-Item
```

