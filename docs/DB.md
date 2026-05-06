# DB / Runtime Data

The current backend uses SQLite plus file storage.

## Runtime Files

- DB: `server/database.sqlite`
- Uploaded and imported media: `server/storage/data/`

Both are ignored by Git. They are local runtime data, not source files.

## Initialization Behavior

`server/db.php` creates the SQLite file, tables, missing columns, and storage directory when the API starts. It does not delete existing rows, images, or settings.

During development, do not delete `server/database.sqlite` or `server/storage/data/` unless you intentionally want a clean board.

## posts Table

```sql
CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  thread_id INTEGER NOT NULL,
  parent_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  url TEXT,
  title TEXT NOT NULL,
  message TEXT NOT NULL,
  image_path TEXT,
  password_hash TEXT,
  created_at TEXT NOT NULL,
  deleted_at TEXT,
  gdgd INTEGER NOT NULL DEFAULT 0,
  tweet_off INTEGER NOT NULL DEFAULT 0,
  tweet_text TEXT,
  tweet_url TEXT,
  tweet_like_count INTEGER NOT NULL DEFAULT 0,
  tweet_retweet_count INTEGER NOT NULL DEFAULT 0,
  tweet_comment_count INTEGER NOT NULL DEFAULT 0,
  tweet_impression_count INTEGER NOT NULL DEFAULT 0
)
```

Tweet statistic columns are kept for compatibility but are no longer exposed in the UI.

## settings Table

```sql
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT NOT NULL
)
```

Settings are stored as JSON sections:

- `config`
- `skin`
- `security`

## Backups

Use the admin screen export button to download one JSON file containing:

- DB rows
- images as base64 payloads
- admin settings

Importing this backup JSON is a full restore. It replaces posts and images, then restores settings from the backup.

## Old BBSnote Import

The admin screen also has `旧BBSnoteログを追加インポート`.

This reads old `LOG_*.cgi` files from a local directory, defaulting to root `data/`, and copies referenced image files into `server/storage/data/`.

This import is intentionally non-destructive:

- It does not delete existing posts.
- It does not delete existing images.
- It does not reset admin settings.
- Re-running it skips posts and replies already imported by matching name, content, and timestamp.

Imported legacy posts use generated unknown password hashes. They should be managed from the admin screen unless a later password migration is implemented.

## Intentional Clean Initialization

For public release or a clean local board:

1. Export a backup first if you need the current data.
2. Stop the PHP server.
3. Delete `server/database.sqlite`.
4. Delete files under `server/storage/data/`, keeping the directory or recreating it later.
5. Start the PHP server again.

The next API request recreates the DB schema and storage directory with default settings.

PowerShell example:

```powershell
Remove-Item server\database.sqlite
Get-ChildItem server\storage\data -File | Remove-Item
```

Use this only when you truly want to reset runtime data.
