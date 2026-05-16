# DB / Runtime Data

[Japanese DB notes](ja/DB.md)

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
  tweet_impression_count INTEGER NOT NULL DEFAULT 0,
  bluesky_uri TEXT,
  bluesky_cid TEXT,
  bluesky_url TEXT,
  bluesky_like_count INTEGER NOT NULL DEFAULT 0,
  bluesky_repost_count INTEGER NOT NULL DEFAULT 0,
  bluesky_quote_count INTEGER NOT NULL DEFAULT 0,
  mastodon_id TEXT,
  mastodon_url TEXT,
  mastodon_boost_count INTEGER NOT NULL DEFAULT 0,
  mastodon_fav_count INTEGER NOT NULL DEFAULT 0,
  misskey_id TEXT,
  misskey_url TEXT,
  misskey_fire_count INTEGER NOT NULL DEFAULT 0,
  misskey_eyes_count INTEGER NOT NULL DEFAULT 0,
  misskey_cry_count INTEGER NOT NULL DEFAULT 0,
  misskey_thinking_count INTEGER NOT NULL DEFAULT 0,
  misskey_party_count INTEGER NOT NULL DEFAULT 0,
  misskey_other_count INTEGER NOT NULL DEFAULT 0,
  user_id INTEGER,
  view_count INTEGER NOT NULL DEFAULT 0
)
```

Social columns store per-platform destination IDs/URLs and cached reaction counts. X keeps the `tweet_*` column names for compatibility.
`user_id` is set only for posts/replies created while logged in. Later "this is my work" links are stored separately so more than one user can claim the same imported or historical post.

## users / user_sessions Tables

```sql
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  login_id TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  display_name TEXT NOT NULL DEFAULT "",
  post_password TEXT NOT NULL DEFAULT "",
  home_url TEXT,
  icon_path TEXT,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS user_sessions (
  token TEXT PRIMARY KEY,
  user_id INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  expires_at TEXT NOT NULL
);
```

## user_post_claims Table

```sql
CREATE TABLE IF NOT EXISTS user_post_claims (
  user_id INTEGER NOT NULL,
  post_id INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  PRIMARY KEY (user_id, post_id)
);
```

This table links a user to a post they marked as their own. It intentionally does not make `post_id` unique, so overlapping claims are allowed.

## access_counts Table

```sql
CREATE TABLE IF NOT EXISTS access_counts (
  access_date TEXT PRIMARY KEY,
  count INTEGER NOT NULL DEFAULT 0
);
```

The frontend records one access when the app shell loads. Admin analytics can graph this as `accessCount`.

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

The `security` section stores `adminPasswordHash` and `cronApiKey`. `cronApiKey` is generated automatically when missing and is used by the protected `cronRefreshSocialReactions` API for external schedulers such as GitHub Actions.

## Backups

Use the admin screen export button to download one JSON file containing:

- DB rows
- images as base64 payloads
- admin settings

Importing this backup JSON is a full restore. It replaces posts and images, then restores settings from the backup.

## Local Archive Import

Local Archive log import is operated from a local operator batch or PHP command instead of the web admin screen. Deployment-local batch files are not part of the committed application unless explicitly added later.

The importer reads `LOG_*.cgi` files from a local directory, defaulting to root `data/`, and copies referenced image files into `server/storage/data/`.

This import is intentionally non-destructive:

- It does not delete existing posts.
- It does not delete existing images.
- It does not reset admin settings.
- Re-running it skips posts and replies already imported by matching name, content, and timestamp.

Imported posts use generated unknown password hashes. They should be managed from the admin screen unless a password migration is implemented.

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
