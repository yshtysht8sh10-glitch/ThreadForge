# file-uploader Frontend

Standalone file uploader frontend.

## Role

- Upload files with comments and delete keys.
- Show upload rows with file metadata and paging.
- Reuse the common ThreadForge backend where possible.
- Share frontend primitives from `../shared`.

## Commands

From the repository root:

```powershell
npm run dev:file-uploader
npm run build:file-uploader
```

Directly in this directory:

```powershell
npm install
npm run dev
npm run build
```

## Runtime Data

Development runtime:

```text
server/runtime/file-uploader/database.sqlite
server/runtime/file-uploader/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/file-uploader/SPEC.md)
- [DB](../../docs/frontends/file-uploader/DB.md)
