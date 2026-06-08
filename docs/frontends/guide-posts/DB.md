# guide-posts DB

[Back to DB index](../../DB.md)

Frontend id: `guide-posts`

## Runtime Files

Development:

```text
server/runtime/guide-posts/database.sqlite
server/runtime/guide-posts/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Current Schema

This frontend currently uses the common ThreadForge SQLite schema initialized by `server/db.php`.

The expected data shape is article/guide oriented:

- each top-level post represents a guide page or article
- uploaded media belongs to the guide frontend only
- future schema may add navigation/category fields if the common settings table is not enough

## Isolation Rule

`guide-posts` must not read or write another frontend's DB or storage directory.
