# ThreadForge Current Specification

ThreadForge is a lightweight, customizable thread board engine for posts, media, replies, moderation, and community archives.

This project began as a modernization of a legacy image board, but the public direction is broader: a self-hosted board engine that can be adapted for artwork, media posts, project logs, small communities, and archives.

## Runtime Structure

- `client/`: React 18 + TypeScript + Vite SPA
- `server/api.php`: PHP API entrypoint
- `server/db.php`: SQLite connection, schema initialization, storage helpers
- `server/database.sqlite`: runtime SQLite DB, ignored by Git
- `server/storage/data/`: uploaded media, ignored by Git except `.gitkeep`
- `docs/`: project documentation

## Routes

- `/`: top thread list
- `/thread/:id`: thread detail, replies, and reply form
- `/thread/:id?mode=eejanaika`: fixed-comment reply form
- `/post`: new top-level post form
- `/search`: search page
- `/delete`: delete mode
- `/edit`: edit mode
- `/edit/:id`: edit selected post or reply
- `/admin`: admin page
- `/manual`: embedded manual page

## Core Features

- Top-level thread posting
- Reply posting
- Image upload for top-level posts only
- Reply image upload disabled
- Tweet text generation for top-level posts only
- Tweet destination URL storage and display for top-level posts
- Tweet OFF mode
- gdgd post mode
- Thread display numbers are assigned only to top-level threads; replies use child reply numbers under their thread.
- Old-board-style top navigation
- Top page shows up to 10 replies per thread
- Thread detail hides Tweet text
- Soft delete with data retained internally
- Edit and delete using post password
- Admin restore for soft-deleted posts
- Admin bulk delete without per-post passwords
- Admin DB integrity check
- Admin backup export/import for DB, images, and settings
- Admin non-destructive import for old BBSnote `LOG_*.cgi` data
- Admin settings storage for `config.cgi` and `skincfg.cgi` equivalents
- Admin-configurable HOME link target
- Admin-configurable embedded manual title and body
- Search by all fields, title, body, or author
- RSS feed

## Admin Features

The admin page uses `DOTEITA_ADMIN_PASSWORD` on the backend.

Implemented:

- Load active and deleted posts
- Bulk delete selected posts or replies without individual post passwords
- Restore deleted posts
- Check DB consistency
- Export backup JSON containing DB rows, images, and settings
- Import backup JSON and restore DB/images/settings
- Import old BBSnote logs from a local directory without resetting existing DB rows, images, or settings
- Edit and save current app settings
- Configure the top navigation HOME link target
- Configure the SPA manual title and body
- Enable or disable Tweet UI
- Enable or disable gdgd posting UI
- Configure the gdgd posting label
- Change the admin password

Notes:

- The legacy CGI "system index repair" is not required in the SQLite version.
- The replacement maintenance function checks DB consistency and missing image references.
- Saved `config` and `skin` settings currently persist through the admin API.
- `homePageUrl`, `manualTitle`, `manualBody`, `tweetEnabled`, `gdgdEnabled`, and `gdgdLabel` are applied to runtime UI through the public settings API.
- Applying every remaining saved setting to runtime behavior and live styling is still in progress.

## Data Model

The main SQLite table is `posts`.

Important columns:

- `id`
- `thread_id`
- `parent_id`
- `name`
- `url`
- `title`
- `message`
- `image_path`
- `password_hash`
- `created_at`
- `deleted_at`
- `gdgd`
- `tweet_off`
- `tweet_text`
- `tweet_url`
- Legacy Tweet statistic columns may exist in the database for compatibility, but they are not exposed in the UI.

The `settings` table stores admin-managed configuration sections as JSON.

Public UI reads a safe subset of settings through `?action=publicSettings`:

- `config.bbsTitle`
- `config.homePageUrl`
- `config.manualTitle`
- `config.manualBody`
- `config.tweetEnabled`
- `config.gdgdEnabled`
- `config.gdgdLabel`

## Storage Rules

- Uploaded images are stored under `server/storage/data/`.
- Runtime DB and uploaded media are excluded from Git.
- Backups should be made through the admin export feature.
- Replacing an existing post image keeps old image files archived rather than deleting them immediately.
- Old BBSnote log import copies legacy images into storage and skips already imported posts/replies by matching name, content, and timestamp.
- Intentional clean initialization is done by stopping the server, deleting `server/database.sqlite`, and deleting files under `server/storage/data/`. See `docs/DB.md`.

## Deletion Rules

Deletion is soft delete:

- Posts disappear from normal UI.
- Data remains in DB with `deleted_at`.
- Admin can restore deleted posts.
- Deleting a top-level thread also soft-deletes its replies.

## Public Naming

The public project name is `ThreadForge`.

Legacy names and references may appear only in migration notes or compatibility documentation. New UI, package metadata, repository metadata, and public documentation should use `ThreadForge`.

## Version Management

The project version is managed with semantic versioning.

Current version: `0.1.0`

Version references must be updated together:

- `VERSION`
- `CHANGELOG.md`
- `client/package.json`
- `client/src/version.ts`

The PHP API exposes the current version through `?action=version`.

## Not Yet Fully Implemented

- Apply all saved `config.cgi`-equivalent settings to runtime limits.
- Apply all saved `skincfg.cgi`-equivalent settings to live styling.
- CSRF protection.
- Production deployment packaging.
- Richer legacy CGI migration options beyond local BBSnote log import.
