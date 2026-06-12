# ThreadForge Docs

The human-facing documentation is generated as bilingual HTML. Open [`index.html`](index.html) and switch Japanese/English on the page.

The Markdown files remain the maintainable source. Regenerate HTML after editing them:

```powershell
npm run build:docs
```

This directory is the English documentation index. Keep the root `README.md` as the short repository entry point, and keep detailed operational notes here.

## Canonical Documents

- `SPEC.md`: specification index and shared contracts
- `../CHANGELOG.md`: release history
- `../CHANGELOG.ja.md`: Japanese release history
- `API.md`: PHP API actions and request/response notes
- `DB.md`: DB/runtime index and shared runtime rules
- `MIGRATION.md`: local archive log import notes
- `ARCHITECTURE.md`: frontend/backend structure
- `FRONTEND_ARCHITECTURE.md`: multi-frontend layout and migration direction
- `TESTING.md`: test commands and coverage notes

## Frontend Documents

Each frontend owns its own README, DB notes, and spec.

- `frontends/image-board/README.md`
- `frontends/image-board/DB.md`
- `frontends/image-board/SPEC.md`
- `frontends/file-uploader/README.md`
- `frontends/file-uploader/DB.md`
- `frontends/file-uploader/SPEC.md`
- `frontends/guide-posts/README.md`
- `frontends/guide-posts/DB.md`
- `frontends/guide-posts/SPEC.md`
- `frontends/proxy-release/README.md`
- `frontends/proxy-release/DB.md`
- `frontends/proxy-release/SPEC.md`
- `frontends/materials-library/README.md`
- `frontends/materials-library/DB.md`
- `frontends/materials-library/SPEC.md`

Japanese versions live in `docs/ja/`, with `../README.ja.md` at the repository root.

## Release And Operator Tools

Release packaging and local archive import scripts live in `tools/`. These are maintainer/operator tools, so the root README keeps only deployment-oriented instructions.

Create a release ZIP:

```powershell
npm run release:image-board
npm run release:file-uploader
```

Release ZIPs are written to `release/threadforge-<frontend-id>-<version>.zip`. Their contents are arranged for upload to a public web directory.

For a GitHub release, update `VERSION` and the changelog, generate and verify the ZIP, then create the matching `v<version>` tag and attach the ZIP to the GitHub Release.

Import local archive logs:

```powershell
tools\import_local_archive.bat legacy\data
```

The local archive importer reads `LOG_*.cgi` files and referenced images from the specified directory. It is non-destructive and is not exposed from the web admin screen.

## Current Runtime Features

- Thread list and detail pages
- Top-level posts with images
- Comment replies without SNS or image controls
- Fixed-comment "eejanaika" replies
- Top page preview of up to 10 replies per thread
- Thread-only display numbers with reply child numbers
- Edit and soft delete
- Admin passwordless bulk delete with start/end range selection, parent-to-reply linked selection, and full/compact display modes
- Admin restore/purge with the same selectable card layout as bulk delete, guarded red purge actions, and full/compact display modes
- Confirmed-entry dedicated admin user-information screen with guarded destructive actions
- Admin DB integrity check
- Admin backup export/import
- Non-destructive local archive log import through a local operator batch, not the web admin screen
- Search by all fields, title, body, or author
- RSS output
- X, Bluesky, Mastodon, and Misskey posting settings are available but disabled by default for local-first operation
- SNS posting previews are generated per enabled platform and long text is shortened with `..`
- SNS posting runs for new top-level posts only; replies and later edits do not post to SNS
- SNS posts include the attached image for X, Bluesky, Mastodon, and Misskey
- SNS posts include a "latest is here" board-list link such as `/#post-000001`
- Cached social reaction counts can be refreshed from the admin maintenance screen, local cron, or an API-key-protected external scheduler URL. `cron.php` rejects keyless browser access and also supports `cron.php?api_key=...` for URL-style schedulers.
- gdgd visual mode
- Embedded manual page
- Admin-configurable HOME link, manual text, SNS platform switches, gdgd switch, gdgd label, and live-preview design colors
- SNS credential fields are disabled in the admin UI while each platform integration is OFF
- Admin password change

## Known Gaps

- Applying every saved `config.cgi`-equivalent setting to runtime limits
- Applying every saved `skincfg.cgi`-equivalent setting to live styling
- CSRF protection
- Production deployment packaging
- Analytics feature implementation
