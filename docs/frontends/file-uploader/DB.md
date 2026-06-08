# file-uploader DB

[Back to DB index](../../DB.md)

Frontend id: `file-uploader`

## Runtime Files

Development:

```text
server/runtime/file-uploader/database.sqlite
server/runtime/file-uploader/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Current Schema

This frontend currently uses the common ThreadForge SQLite schema initialized by `server/db.php`.

The expected data shape is uploader-oriented:

- uploaded files are stored in `storage/data/`
- file entries use post rows until a dedicated upload table is introduced
- comments, delete keys/passwords, size metadata, and paging are frontend-level behavior for this app

## Isolation Rule

`file-uploader` must not read or write another frontend's DB or storage directory.
