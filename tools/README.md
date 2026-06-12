# ThreadForge Tools

[Japanese tools guide](README.ja.md)

Operational scripts for maintainers and site operators live in this directory.

## Release ZIP

Create a distribution archive:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

The archive is written to `release/threadforge-<frontend-id>-<version>.zip`.

The release ZIP is arranged for simple rental-server deployment. Upload the extracted directory to `/DotoEita/`.

```text
11_image_board/ or 12_file_uploader/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

The frontend is built to call `./api.php` on the same site. The release ZIP includes the built frontend, PHP backend, and documentation. Operator scripts are not included in the release ZIP. Runtime data is intentionally excluded:

- development DBs under `server/runtime/<frontend-id>/database.sqlite`
- development uploaded images under `server/runtime/<frontend-id>/storage/data/`
- deployed release DB as `database.sqlite`
- deployed release uploaded images under `storage/data/`
- local PHP binaries
- dependency directories
- logs
- legacy source data

On first access, ThreadForge creates the SQLite DB automatically when PHP has write permission for the deployed directory. In a release ZIP deployment, make sure `storage/data/` is writable so uploaded images can be saved.

Set `THREADFORGE_FRONTEND_ID` per frontend/API pair when multiple frontends are deployed. For example, use `image-board` for the image posting board and `file-uploader` for the file uploader.

## Local Archive Import

Import local archive logs into the current SQLite DB:

```powershell
tools\import_local_archive.bat legacy\data
```

The importer reads `LOG_*.cgi` files and referenced images from the specified directory. The import is non-destructive: it does not delete existing posts, images, or settings, and re-running it skips matching imported posts and replies.

## ThreadForge Archive Folder Import

Import multiple archive folders, such as `legacy/import_data`, in parent-post chronological order:

```powershell
tools\import_threadforge_archives.bat legacy\import_data
```

Folder rules:

- `bbs1-999`: detect normal/special posts from archive markers.
- `bbs10_DoteitaArchive_Doteita`: import as normal posts.
- `bbs20_DoteitaArchive_gdgd`: import as special posts.
- `bbsOO_DoteitaArchive`: import as special only when the title contains `gdgd` or legacy gdgd markers.

## Imported Image Repair

If archive-imported post text and images become mismatched after maintenance work, repair the image files from the original archive logs without re-importing posts:

```powershell
tools\repair_imported_images.bat legacy\import_data
tools\repair_imported_images.bat legacy\import_data --apply --offset=0 --limit=200
```

The first command is a dry run. Add `--apply` to copy the correct image for each matching imported post and update `image_path` in the current DB.
The output includes one line per planned/applied image change: post ID, title, created date, source image, and destination image.

On rental-server cron panels that accept only a PHP file path, run:

```text
/path/to/threadforge/tools/repair_imported_images_cron.php
```

The cron wrapper applies the repair in batches of 200 and writes `repair_imported_images.log` under the ThreadForge directory. The log includes the summary and one `changed` line for each image that was repaired.

If the archive directory is not the default `legacy/import_data`, pass it with the `archive` query parameter:

```text
https://example.com/threadforge/tools/repair_imported_images_cron.php?archive=legacy/data
```

Add `reset=1` to restart from the beginning. Use `limit` to change the batch size:

```text
https://example.com/threadforge/tools/repair_imported_images_cron.php?reset=1&limit=100
```

## Recent Imported Post Update

When the latest BBSNote archive entries were updated after import, update only the newest N imported parent posts without changing ThreadForge post IDs or display numbers. Run a dry run first:

```powershell
tools\update_imported_recent.bat legacy\import_data --limit=10
tools\update_imported_recent.bat legacy\import_data --limit=10 --apply
tools\update_imported_recent.bat legacy\import_data --limit=10 --apply --add
```

On a rental server, open the cron wrapper in a browser or run it from cron:

```text
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10&apply=1
https://example.com/threadforge/tools/update_imported_recent_cron.php?limit=10&apply=1&add=1
```

Without `apply=1`, it only reports planned changes. `--add` or `add=1` inserts unmatched latest archive entries as new ThreadForge posts; without it, unmatched entries are reported as `new_candidates` only. Results are written to `update_imported_recent.log`.
