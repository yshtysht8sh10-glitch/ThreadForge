# DB / Runtime Data

[Back to DB index](../../DB.md)
[Japanese image-board DB notes](../../../ja/frontends/image-board/DB.md)

The backend uses SQLite plus file storage. Backend code is shared, but runtime data is separated by frontend during development.
Release ZIPs are packaged as single-frontend apps, so each deployed ZIP uses local `database.sqlite` and `storage/data/`.

## Runtime Files

Development runtime files:

- DB: `server/runtime/<frontend-id>/database.sqlite`
- Uploaded and imported media: `server/runtime/<frontend-id>/storage/data/`

Packaged release runtime files:

- DB: `database.sqlite`
- Uploaded and imported media: `storage/data/`

Both are ignored by Git. They are local runtime data, not source files.

For example:

```text
server/runtime/image-board/database.sqlite
server/runtime/image-board/storage/data/
server/runtime/file-uploader/database.sqlite
server/runtime/file-uploader/storage/data/
```

`image-board` and `file-uploader` never share DB rows, settings, users, sessions, or uploaded files when they run with different frontend ids.

## Frontend Runtime Selection

Set `THREADFORGE_FRONTEND_ID` for each deployed frontend/API pair.

```powershell
$env:THREADFORGE_FRONTEND_ID = 'image-board'
php -S 127.0.0.1:8000 -t server
```

```powershell
$env:THREADFORGE_FRONTEND_ID = 'file-uploader'
php -S 127.0.0.1:8001 -t server
```

The value is sanitized to letters, numbers, hyphens, and underscores before it is used in paths.

Advanced overrides still work:

- `THREADFORGE_DB_FILE`: exact SQLite file path
- `THREADFORGE_STORAGE_DIR`: exact uploaded-file directory
- `THREADFORGE_STORAGE_PUBLIC_BASE`: public URL prefix for stored files

## Initialization Behavior

`server/db.php` creates the selected SQLite file, tables, missing columns, and storage directory when the API starts. It does not delete existing rows, images, or settings.

During development, do not delete `server/runtime/<frontend-id>/database.sqlite` or `server/runtime/<frontend-id>/storage/data/` unless you intentionally want a clean board. In a deployed release ZIP, do not delete `database.sqlite` or `storage/data/` unless you intentionally want a clean app.

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

## post_revisions Table

```sql
CREATE TABLE IF NOT EXISTS post_revisions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  post_id INTEGER NOT NULL,
  revised_at TEXT NOT NULL
);
```

Each successful edit inserts one row. The API exposes the count as `revision_count` and the edit timestamps as `revision_dates`; the UI displays this as `rev01`, `rev02`, etc.

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

When the DB is fresh and the settings table has no rows, ThreadForge uses application defaults: gdgd/special posting OFF, list page size 20, SNS hashtag `#art`, all SNS integrations OFF, and SNS credential fields empty. An empty or non-positive list page size shows all posts on one page.

## Backups

Use the admin maintenance screen full backup export button to download one ZIP file containing:

- posts and replies
- edit revisions
- users
- claimed works
- access-count history
- settings
- post images and user icons under `images/`

Login sessions are intentionally not included; users sign in again after restore.

Importing this full backup ZIP is a full restore. It replaces backed-up DB rows and images, then restores settings from the backup. Legacy JSON backups from earlier versions remain import-compatible.

## Local Archive Import

Local Archive log import is operated from a local operator batch or PHP command instead of the web admin screen.

```powershell
tools\import_local_archive.bat legacy\data
```

The importer reads `LOG_*.cgi` files from a local directory, defaulting to root `data/`, and copies referenced image files into the active frontend's storage directory.

This import is intentionally non-destructive:

- It does not delete existing posts.
- It does not delete existing images.
- It does not reset admin settings.
- Re-running it skips posts and replies already imported by matching name, content, and timestamp.

Imported posts use generated unknown password hashes. They should be managed from the admin screen unless a password migration is implemented.

## Release ZIP

Create a distribution archive with:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\build_release.ps1
```

The script creates `release/threadforge-image-board-<version>.zip` from a whitelist of application files. The archive contains a `11_image_board/` deployment directory with:

- `11_image_board/index.html`
- `11_image_board/assets/`
- `11_image_board/api.php`
- `11_image_board/db.php`
- `11_image_board/cron.php`
- `11_image_board/storage/data/.gitkeep`
- `11_image_board/docs/`

Runtime DB files, uploaded images, local PHP binaries, logs, dependency directories, operator scripts, and legacy import source data are intentionally not included. A deployed site should be updated by backing up runtime data first, then replacing application files while preserving `database.sqlite` and `storage/data/`.

On a clean deployment, the next API request creates `database.sqlite` and the storage directory automatically when PHP has write permission.

## Intentional Clean Initialization

For public release or a clean local board:

1. Export a backup first if you need the current data.
2. Stop the PHP server.
3. During development, delete `server/runtime/<frontend-id>/database.sqlite`. In a deployed release ZIP, delete `database.sqlite`.
4. During development, delete files under `server/runtime/<frontend-id>/storage/data/`. In a deployed release ZIP, delete files under `storage/data/`. Keep the directory or recreate it later.
5. Start the PHP server again.

The next API request recreates the DB schema and storage directory with default settings.

PowerShell example:

```powershell
Remove-Item server\database.sqlite
Get-ChildItem server\storage\data -File | Remove-Item
```

Use this only when you truly want to reset runtime data.
