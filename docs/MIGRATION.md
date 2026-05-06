# Migration Notes

## Old BBSnote Logs

ThreadForge can import old BBSnote-style `LOG_*.cgi` files from the admin screen.

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

Imported legacy posts use generated unknown password hashes. Manage them through the admin screen.

## Full Backup Restore

The backup JSON import is different from old-log import. It is a full restore and replaces posts/images with the backup contents.

Use backup restore when moving a ThreadForge instance. Use old BBSnote import when adding historical logs to an existing board.
