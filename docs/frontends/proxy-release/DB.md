# proxy-release DB

[Back to DB index](../../DB.md)

Frontend id: `proxy-release`

## Runtime Files

Development:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Current Schema

This frontend currently uses the common ThreadForge SQLite schema initialized by `server/db.php`.

The expected data shape is release-oriented:

- each top-level post represents a proxy release entry
- downloadable packages and images are stored under this frontend's storage
- future schema may add release metadata if title/message/settings are not enough

## Isolation Rule

`proxy-release` must not read or write another frontend's DB or storage directory.
