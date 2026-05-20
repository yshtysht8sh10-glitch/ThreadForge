# ThreadForge

[Japanese README](README.ja.md)

ThreadForge is a lightweight, customizable thread board engine for posts, media, and community archives. It is designed as a fresh self-hosted board for posts, media, and community archives.

## Repository Contents

- `client/`: React, TypeScript, Vite frontend
- `server/`: PHP API, SQLite storage, PHPUnit tests
- `docs/`: architecture, API, DB, migration, and testing notes
- `docs/SPEC.md`: current product specification
- `docs/ja/SPEC.md`: Japanese product specification
- `tools/`: release and operator scripts

Local archive files and historical image/log data are kept out of this Git repository by default. They are useful as migration/reference material, but they are not required to run the current app.

## Local Setup

Frontend:

```powershell
cd client
copy .env.example .env
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

Backend:

```powershell
cd server
composer install
php -S 127.0.0.1:8000 -t .
```

On first access to the admin screen, set the administrator password in the browser. For recovery or scripted setup, you may also start the server with `THREADFORGE_ADMIN_PASSWORD`.

Then open:

```text
http://127.0.0.1:5173
```

## Tests

Frontend:

```powershell
cd client
npm test -- --run
npm run build
```

Backend:

```powershell
cd server
vendor/bin/phpunit
```

## Versioning

The project version is tracked in:

- `VERSION`
- `CHANGELOG.md`
- `client/package.json`
- `client/src/version.ts`

Use semantic versioning. Update all version references and the changelog in the same commit.

## Runtime Data

The backend creates runtime files locally:

- `server/database.sqlite`
- `server/storage/data/*`

These files are intentionally ignored by Git. Use the admin backup/export feature to move live data between environments.

## Rental Server Deployment

Extract the release ZIP and upload the contents of the `threadforge-<version>` directory to your rental server's public web directory.

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

The frontend calls `./api.php` on the same site. Runtime DB files and uploaded images are intentionally excluded from the ZIP. On first access, the SQLite DB is created automatically if PHP can write to the deployed directory.

`storage/data/` stores uploaded images. Set write permission for it on the rental server when necessary.

## Social Posting Operation

X, Bluesky, Mastodon, and Misskey integrations are disabled by default. In this operating mode, posts are saved locally without calling external APIs. Each platform has its own admin settings group, and credential fields stay disabled while that platform switch is OFF.

When SNS posting is enabled, top-level posts can be copied to enabled SNS platforms at creation time. X, Bluesky, Mastodon, and Misskey receive the post image when one is attached. SNS text includes a "latest is here" link back to the top list anchor for the board post, and long text is trimmed with `..` instead of blocking submission.

Post edits update the board only and do not edit or repost to SNS. Cached reaction counts can be refreshed manually from the admin maintenance screen or automatically through either the local `server/cron.php` script or the protected API URL shown in the admin screen. Automatic reaction refresh targets all non-deleted top-level posts that have SNS post IDs.

## Documentation Map

- `docs/SPEC.md`: current product specification and implementation status
- `CHANGELOG.md`: release history
- `CHANGELOG.ja.md`: Japanese release history
- `docs/README.md`: documentation index
- `docs/API.md`: PHP API reference
- `docs/DB.md`: SQLite/runtime data, backup, import, and reset notes
- `docs/MIGRATION.md`: local archive log import notes
- `docs/ARCHITECTURE.md`: architecture notes
- `docs/TESTING.md`: test strategy
- `README.ja.md`: Japanese README
- `docs/ja/SPEC.md`: Japanese product specification
- `docs/ja/`: Japanese documentation
