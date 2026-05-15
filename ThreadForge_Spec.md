# ThreadForge Current Specification

[Japanese specification](ThreadForge_Spec.ja.md)

ThreadForge is a lightweight, customizable thread board engine for posts, media, replies, moderation, and community archives.

ThreadForge is presented as a new self-hosted board engine that can be adapted for artwork, media posts, project logs, small communities, and archives.

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
- SNS posting previews and posting for top-level posts only
- X, Bluesky, Mastodon, and Misskey integrations are disabled by default
- SNS destination URL/ID storage and cached reaction display for top-level posts
- SNS transfer OFF mode
- gdgd post mode
- Thread display numbers are assigned only to top-level threads; replies use child reply numbers under their thread
- Classic board-style top navigation
- Top page shows up to 10 replies per thread
- Thread detail keeps users on the same page after submitting comments or "eejanaika"
- Soft delete with data retained internally
- Edit and delete using post password
- Admin restore for soft-deleted posts
- Admin bulk delete without per-post passwords
- Admin DB integrity check
- Admin backup export/import for DB, images, and settings
- Local, non-destructive import for local archive `LOG_*.cgi` data through an operator batch or PHP command
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
- Import local archive logs from a local directory without resetting existing DB rows, images, or settings
- Edit and save current app settings
- Configure the top navigation HOME link target
- Configure the SPA manual title and body
- Enable or disable each SNS integration
- Enable or disable gdgd posting UI
- Configure the gdgd posting label
- Change the admin password
- Refresh cached SNS reaction counts
- Show local cron path and API-key-protected scheduler URL for automated SNS reaction refresh

Notes:


- The replacement maintenance function checks DB consistency and missing image references.
- Saved `config` and `skin` settings currently persist through the admin API.
- `homePageUrl`, `manualTitle`, `manualBody`, SNS platform switches, `gdgdEnabled`, `gdgdLabel`, and basic colors are applied to runtime UI through the public settings API.
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
- X/Tweet statistic columns for likes, reposts, impressions
- Bluesky URI/CID/URL and Like/Repost/Quote counts
- Mastodon ID/URL and Boost/Fav counts
- Misskey ID/URL and reaction counts

The `settings` table stores admin-managed configuration sections as JSON. The `security` section includes `adminPasswordHash` and `cronApiKey`.

Public UI reads a safe subset of settings through `?action=publicSettings`:

- `config.bbsTitle`
- `config.homePageUrl`
- `config.manualTitle`
- `config.manualBody`
- `config.tweetEnabled`
- `config.blueskyEnabled`
- `config.mastodonEnabled`
- `config.misskeyEnabled`
- `config.gdgdEnabled`
- `config.gdgdLabel`

## SNS Posting Rules

- SNS posting is controlled by one user-facing "SNS transfer OFF" switch on post and edit forms.
- The admin page separates settings for X, Bluesky, Mastodon, and Misskey.
- Platform credential fields are disabled while that platform is OFF.
- New top-level posts can be reflected to enabled SNS platforms.
- Top-level post edits update the board only and do not edit or repost to SNS.
- Replies never post to SNS and do not show SNS controls.
- Attached images are sent to X, Bluesky, Mastodon, and Misskey when the platform is enabled.
- Preview text is shown per enabled platform. The preview is read-only.
- Text after `_TWEND_` is excluded from SNS posting.
- SNS text includes a "latest is here" board-list link such as `/#post-000001`; the top list scrolls to the matching post anchor.
- If text exceeds a platform limit, ThreadForge shortens it with `..` and still submits.
- Current default limits are X 280, Bluesky 300, Mastodon 500, and Misskey 3000.
- X uses weighted character counting; the other platform previews use normal character length.
- Cached reaction counts are displayed as simple same-color metrics, with platform destination markers aligned by row.
- Cached reaction refresh can be run manually from admin maintenance, by local `server/cron.php`, or by the API endpoint `?action=cronRefreshSocialReactions&api_key=...`.
- Automatic reaction refresh targets only non-deleted top-level posts created within the last 7 days.

## Comment and Fixed-Comment Forms

- Comment forms use name, URL/HOME, body, and password fields.
- Name, body, and password are required for comments.
- Password is limited to 8 alphanumeric characters by UI guidance.
- Fixed-comment forms require a name and choose one fixed reply text.
- Inline comment and fixed-comment forms have close buttons. Closing a draft asks for confirmation.
- When an inline form is open, the thread action buttons are hidden.

## Storage Rules

- Uploaded images are stored under `server/storage/data/`.
- Runtime DB and uploaded media are excluded from Git.
- Backups should be made through the admin export feature.
- Replacing an existing post image archives the previous image file instead of deleting it immediately.
- Local archive import copies source images into storage and skips already imported posts/replies by matching name, content, and timestamp. It is run locally, not through the web admin screen.
- Intentional clean initialization is done by stopping the server, deleting `server/database.sqlite`, and deleting files under `server/storage/data/`. See `docs/DB.md`.

## Deletion Rules

Deletion is soft delete:

- Posts disappear from normal UI.
- Data remains in DB with `deleted_at`.
- Admin can restore deleted posts.
- Deleting a top-level thread also soft-deletes its replies.

## Public Naming

The public project name is `ThreadForge`.

Public UI, package metadata, repository metadata, and public documentation should present `ThreadForge` as a new standalone product.

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
- Analytics feature implementation is the next TODO.
