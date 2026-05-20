# ThreadForge Tools

[Japanese tools guide](README.ja.md)

Operational scripts for maintainers and site operators live in this directory.

## Release ZIP

Create a distribution archive:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

The archive is written to `release/threadforge-<version>.zip`.

The release ZIP is arranged for simple rental-server deployment. Upload the extracted `threadforge-<version>` directory contents to the public web directory.

```text
threadforge-<version>/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

The frontend is built to call `./api.php` on the same site. The release ZIP includes the built frontend, PHP backend, and documentation. Operator scripts are not included in the release ZIP. Runtime data is intentionally excluded:

- `server/database.sqlite`
- uploaded images under `server/storage/data/`
- local PHP binaries
- dependency directories
- logs
- legacy source data

On first access, ThreadForge creates the SQLite DB automatically when PHP has write permission for the deployed directory. Make sure `storage/data/` is writable so uploaded images can be saved.

## Local Archive Import

Import local archive logs into the current SQLite DB:

```powershell
tools\import_local_archive.bat legacy\data
```

The importer reads `LOG_*.cgi` files and referenced images from the specified directory. The import is non-destructive: it does not delete existing posts, images, or settings, and re-running it skips matching imported posts and replies.
