# ThreadForge

[Japanese README](README.ja.md)

ThreadForge is a lightweight, customizable thread board engine for posts, media, and community archives. It is designed as a fresh self-hosted board for posts, media, and community archives.

## Application Versions

ThreadForge is a monorepo, but every application has an independent semantic version.

<!-- app-versions:start -->
| App | Current Version | Source of Truth |
| --- | --- | --- |
| Image Board | 0.9.7 | `frontends/image-board/package.json` |
| File Uploader | 1.0.2 | `frontends/file-uploader/package.json` |
| Document Holder | 0.9.7 | `frontends/document-holder/package.json` |
| Materials Library | 0.9.2 | `frontends/materials-library/package.json` |
| Proxy Release | 0.9.10 | `frontends/proxy-release/package.json` |
<!-- app-versions:end -->

Run `npm run versions` to read the authoritative values mechanically.

## Repository Contents

- `frontends/image-board/`: current production image posting board frontend
- `frontends/shared/`: shared frontend code for future frontend apps
- `frontends/file-uploader/`: file uploader frontend
- `frontends/document-holder/`: document holder frontend for articles, HTML folders, and guide documents
- `frontends/proxy-release/`: proxy release frontend
- `frontends/materials-library/`: production archive-first materials library
- `server/`: PHP API, SQLite storage, PHPUnit tests
- `docs/index.html`: bilingual Japanese/English documentation
- `docs/`: Markdown sources, generated HTML, architecture, API, DB, migration, and testing notes
- `tools/`: release and operator scripts

Local archive files and historical image/log data are kept out of this Git repository by default. They are useful as migration/reference material, but they are not required to run the current app.

## Local Setup

Root shortcut:

```powershell
npm run dev:image-board
npm run dev:file-uploader
npm run dev:document-holder
npm run dev:proxy-release
npm run dev:materials-library
```

Frontend:

```powershell
cd frontends/image-board
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

From the repository root:

```powershell
npm run test:image-board
npm run build:image-board
npm run build:frontends
```

Frontend:

```powershell
cd frontends/image-board
npm test -- --run
npm run build
```

Backend:

```powershell
cd server
vendor/bin/phpunit
```

## Versioning

Each `frontends/<frontend-id>/package.json` is the single source of truth for that application. There is intentionally no repository-wide release version. Change only the version of an application being released, update its changelog entry, and leave unrelated application versions unchanged. `npm run versions:check` validates the version structure and SemVer values.

New tags use `<app-name>-vX.Y.Z`, such as `proxy-release-v0.9.8`. GitHub Release names include the application name, such as `Proxy Release 0.9.8`. Historical tags, including the old unscoped `vX.Y.Z` Image Board tags, remain unchanged.

## Runtime Data

The backend creates development runtime files locally per frontend id:

- `server/runtime/<frontend-id>/database.sqlite`
- `server/runtime/<frontend-id>/storage/data/*`

Release ZIPs are standalone single-frontend apps. In a deployed ZIP, runtime files live next to the packaged app:

- `database.sqlite`
- `storage/data/*`

These files are intentionally ignored by Git. Use the admin full backup ZIP import/export feature to move live data between environments.

Before updating a live site, export a full backup ZIP from the admin maintenance screen and separately keep a copy of that frontend's `database.sqlite` and `storage/data/`.

## Rental Server Deployment

Extract the release ZIP and upload the included deployment directory to `/DotoEita/`.

```text
11_image_board/
  index.html
  assets/
  api.php
  db.php
  cron.php
  storage/data/
  docs/
```

The image-board ZIP contains `11_image_board/`. The file-uploader ZIP contains `12_file_uploader/`.
The materials-library ZIP contains `15_materials_library/`. The proxy-release ZIP contains `16_proxy_release/`.

The frontend calls `./api.php` on the same site. Runtime DB files and uploaded images are intentionally excluded from the ZIP. On first access, the SQLite DB is created automatically if PHP can write to the deployed directory.

`storage/data/` stores uploaded images. Set write permission for it on the rental server when necessary.

Fresh installations start with the standard dark board design, gdgd/special posting OFF, list page size 20, SNS hashtag `#art`, all SNS integrations OFF, and SNS credential fields empty. Leaving the list page size empty shows all posts on one page.

## Operator Tools

Release packaging and maintenance scripts live in `tools/`. They are not included in the public release ZIP.

- `build_release.ps1`: reads the selected application's `package.json` and builds `release/threadforge-<frontend-id>-<version>.zip`
- `import_threadforge_archives.*`: imports BBSNote/local archive folders
- `repair_imported_images.*`: reattaches correct images from legacy logs without re-importing posts
- `update_imported_recent.*`: updates the newest imported archive entries and can insert unmatched latest posts with `--add` / `add=1`

See `tools/README.md` and `tools/README.ja.md` for details.

## Social Posting Operation

X, Bluesky, Mastodon, and Misskey integrations are disabled by default. In this operating mode, posts are saved locally without calling external APIs. Each platform has its own admin settings group, and credential fields stay disabled while that platform switch is OFF.

When SNS posting is enabled, top-level posts can be copied to enabled SNS platforms at creation time. X, Bluesky, Mastodon, and Misskey receive the post image when one is attached. SNS text includes a "latest is here" link back to the top list anchor for the board post, and long text is trimmed with `..` instead of blocking submission.

Post edits update the board only and do not edit or repost to SNS. Cached reaction counts can be refreshed manually from the admin maintenance screen or automatically through either the local `server/cron.php` script or the protected API URL shown in the admin screen. Automatic reaction refresh targets all non-deleted top-level posts that have SNS post IDs.
`cron.php` rejects keyless browser access. If your scheduler calls URLs instead of local files, use `cron.php?api_key=...` or the protected API URL shown in the admin screen.

## Documentation Map

- `docs/index.html`: bilingual documentation index
- `docs/SPEC.html`: current product specification and implementation status
- `CHANGELOG.md`: release history
- `CHANGELOG.ja.md`: Japanese release history
- `docs/API.html`: PHP API reference
- `docs/DB.html`: SQLite/runtime data, backup, import, and reset notes
- `docs/MIGRATION.html`: local archive log import notes
- `docs/ARCHITECTURE.html`: architecture notes
- `docs/TESTING.html`: test strategy
- `docs/VERSIONING.html`: independent application versioning and release rules
- `README.ja.md`: Japanese README
