# ThreadForge Current Specification

[Japanese specification](ja/SPEC.md)

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
- Admin restore/purge for soft-deleted posts
- Admin bulk delete without per-post passwords, including display-number range selection
- Admin DB integrity check
- Admin full backup export/import for posts, replies, edit revisions, users, claimed works, access history, images, and settings
- Local, non-destructive import for local archive `LOG_*.cgi` data through an operator batch or PHP command
- Admin settings storage for `config.cgi` and `skincfg.cgi` equivalents
- Admin-configurable HOME link target
- Admin-configurable embedded manual title and body
- Search by all fields, title, body, or author
- RSS feed

## Admin Features

The admin page uses the configured administrator password. `THREADFORGE_ADMIN_PASSWORD` is available as an optional recovery/setup override.

Implemented:

- Load active and deleted posts
- Bulk delete selected posts or replies without individual post passwords, or select top-level display-number ranges
- Restore/purge deleted posts. Purge can erase post data while keeping display numbers, or remove rows and compact top-level display numbers
- Manage registered users: edit user information, erase user information while keeping the user number, or delete the user row and compact user numbers
- Check DB consistency
- Export full backup ZIP containing posts, replies, edit revisions, users, claimed works, access history, images, and settings
- Import full backup ZIP and restore DB rows/images/settings. Legacy JSON backups remain import-compatible. Login sessions are intentionally not included.
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
- Fresh DB defaults: gdgd posting OFF, list page size 20, SNS hashtag `#art`, all SNS integrations OFF, and SNS credential fields empty
- Empty or non-positive list page size means all posts are shown on one page
- The admin UI displays maximum upload size in KB while storing it internally as bytes

Notes:


- The replacement maintenance function checks DB consistency and missing image references.
- Saved `config` and `skin` settings currently persist through the admin API.
- `homePageUrl`, `manualTitle`, `manualBody`, SNS platform switches, `gdgdEnabled`, `gdgdLabel`, and basic colors are applied to runtime UI through the public settings API.
- Applying every remaining saved setting to runtime behavior and live styling is still in progress.

## Parent Site SSO

ThreadForge can accept signed SSO tokens from a parent site so users logged in on the parent site can enter a child ThreadForge board.

- Enable "Use SSO" in board settings and configure the shared SSO secret.
- The shared secret is empty by default. The settings screen can generate a cryptographically secure 64-character secret after confirmation and copy it for use by the parent site. The generated value is not persisted until settings are saved.
- When SSO is enabled, users cannot create new accounts from the ThreadForge login screen. The login screen tells users to create accounts on the parent site instead.
- When SSO is enabled, the registered-user management button on the maintenance screen is disabled, so the child-site user management screen cannot be opened. User profile changes and deletion should be handled by the parent site.
- The parent site sends users to `index.html#/login?sso=<token>`.
- The token format is `base64url(payload).base64url(hmac_sha256(base64url(payload), secret))`.
- The payload must include `login_id` or `sub`, and `exp`. It may also include `display_name`, `post_password`, `home_url`, and `iat`.
- ThreadForge verifies the signature and expiration, then logs in the existing user or creates the user from the SSO payload.

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
- Posts created with social transfer disabled show only the board metrics row; X/Bluesky/Mastodon/Misskey destination rows are hidden.
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
- Cached reaction refresh can be run manually from admin maintenance, by local `server/cron.php`, by `server/cron.php?api_key=...`, or by the API endpoint `?action=cronRefreshSocialReactions&api_key=...`.
- `server/cron.php` rejects keyless browser access. URL-style schedulers can call it with the API key, and those runs are logged as `cron web social reaction ...`.
- Automatic reaction refresh targets all non-deleted top-level posts that have SNS post IDs.

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
- Release ZIPs are created with `tools/build_release.ps1`. The archive is arranged for direct upload to a public web directory with `index.html`, `assets/`, `api.php`, `db.php`, `cron.php`, and `storage/data/` at the top level. Runtime `database.sqlite`, uploaded images, and operator scripts are not included.
- Intentional clean initialization is done by stopping the server, deleting `server/database.sqlite`, and deleting files under `server/storage/data/`. See `docs/DB.md`.

## Deletion Rules

Normal deletion is soft delete:

- Posts disappear from normal UI.
- Data remains in DB with `deleted_at`.
- Admin can restore deleted posts from the restore/purge screen.
- Deleting a top-level thread also soft-deletes its replies.
- The restore/purge screen supports two destructive admin operations for deleted posts.
- Stage 1 purge erases content, image references, replies, SNS metadata, edit revisions, and user claims while preserving the top-level display number.
- Stage 2 number purge physically deletes the post row. When a top-level post is removed this way, later top-level display numbers close up.
- Bulk delete and restore/purge support range input such as `1-10, 15` for top-level display numbers.

## User Administration

The maintenance tab can manage registered users.

- Editing a user first shows a confirmation message. If confirmed, admin can edit login ID, display name, post password, URL/HOME, and optionally reset the login password.
- User edits validate duplicate login IDs and the 30-character display-name limit.
- User deletion has two stages.
- Stage 1 erases personal user information and sessions while preserving the user number.
- Stage 2 physically deletes the user row, clears direct post ownership references for that user, and compacts remaining user numbers.

## Public Naming

The public project name is `ThreadForge`.

Public UI, package metadata, repository metadata, and public documentation should present `ThreadForge` as a new standalone product.

## Version Management

The project version is managed with semantic versioning.

Current version: `0.9.6`

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
