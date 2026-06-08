# materials-library DB

[Back to DB index](../../DB.md)

Frontend id: `materials-library`

## Runtime Files

Development:

```text
server/runtime/materials-library/database.sqlite
server/runtime/materials-library/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Current Schema

This frontend currently uses the common ThreadForge SQLite schema initialized by `server/db.php`.

The expected data shape is material-card oriented:

- each top-level post represents a downloadable material or material group
- uploaded files and preview images are stored under this frontend's storage
- future schema may add usage flags/license fields if the common post/settings data is not enough

## Isolation Rule

`materials-library` must not read or write another frontend's DB or storage directory.
