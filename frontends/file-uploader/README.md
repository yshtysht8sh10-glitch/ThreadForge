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

- [Bilingual documentation / 日英切替ドキュメント](../../docs/frontends/file-uploader/index.html)
- [Spec / 仕様](../../docs/frontends/file-uploader/SPEC.html)
- [DB](../../docs/frontends/file-uploader/DB.html)

## Release

```powershell
npm run test:file-uploader
npm run release:file-uploader
```

The standalone ZIP is written to `release/threadforge-file-uploader-1.0.0.zip`.
