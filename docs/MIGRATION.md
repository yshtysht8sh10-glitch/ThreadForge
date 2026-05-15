# Migration Notes

[Japanese migration notes](ja/MIGRATION.md)

## Local Archive Logs

ThreadForge can import local archive-style `LOG_*.cgi` files, but this operation is intentionally kept out of the web admin screen. Run it from a local operator batch or local PHP command that calls `importLocalArchiveDirectory()` in `server/db.php`.

Default source directory:

- `data/`

Expected layout:

```text
data/
  LOG_009359.cgi
  LOG_009360.cgi
  DOTIMG_009359_1.gif
  DOTIMG_009360.png
```

Import behavior:

- The first line of each `LOG_*.cgi` file becomes a top-level thread.
- Later lines become replies under that thread.
- Referenced images are copied into `server/storage/data/`.
- Existing DB rows, uploaded images, and admin settings are not reset.
- Re-running the import skips already imported posts and replies by name, content, and timestamp.

Imported posts use generated unknown password hashes. Manage them through the admin screen.
The local batch used for an individual deployment should not be committed unless the project explicitly decides to ship one.

## Full Backup Restore

The backup JSON import is different from local archive import. It is a full restore and replaces posts/images with the backup contents.

Use backup restore when moving a ThreadForge instance. Use local archive import when adding historical logs to an existing board.
