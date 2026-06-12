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

This frontend uses the common settings/admin tables plus a dedicated `uploader_files` table initialized by `server/db.php`.

```sql
CREATE TABLE IF NOT EXISTS uploader_files (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  stored_name TEXT NOT NULL,
  original_name TEXT NOT NULL,
  comment TEXT NOT NULL DEFAULT "",
  size_bytes INTEGER NOT NULL,
  delete_key_hash TEXT NOT NULL,
  created_at TEXT NOT NULL,
  deleted_at TEXT
);
```

- Uploaded files are stored in this frontend's `storage/data/`.
- Delete keys are stored as password hashes.
- The original filename and generated storage filename are kept separately.
- Allowed extensions and maximum size are read from this frontend's settings.
- Rows with `deleted_at` are shown in the administrator restore/purge view.
- Restore clears `deleted_at`; purge removes both the DB row and stored file.
- The maintenance full backup ZIP contains this frontend's `database.sqlite` and every file under `storage/data/`.

## Isolation Rule

`file-uploader` must not read or write another frontend's DB or storage directory.
