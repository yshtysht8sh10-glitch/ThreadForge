# ThreadForge Docs

[Japanese docs](ja/README.md)

This directory is the English documentation index. Keep the root `README.md` as the short repository entry point, and keep detailed operational notes here.

## Canonical Documents

- `../ThreadForge_Spec.md`: current product specification and implementation status
- `../CHANGELOG.md`: release history
- `../CHANGELOG.ja.md`: Japanese release history
- `API.md`: PHP API actions and request/response notes
- `DB.md`: SQLite schema, runtime data, backups, imports, and reset procedures
- `MIGRATION.md`: local archive log import notes
- `ARCHITECTURE.md`: frontend/backend structure
- `TESTING.md`: test commands and coverage notes

Japanese versions live in `docs/ja/`, with `../README.ja.md` and `../ThreadForge_Spec.ja.md` at the repository root.

## Current Runtime Features

- Thread list and detail pages
- Top-level posts with images
- Comment replies without SNS or image controls
- Fixed-comment "eejanaika" replies
- Top page preview of up to 10 replies per thread
- Thread-only display numbers with reply child numbers
- Edit and soft delete
- Admin restore and passwordless bulk delete
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
- Cached social reaction counts can be refreshed from the admin maintenance screen, local cron, or an API-key-protected external scheduler URL
- gdgd visual mode
- Embedded manual page
- Admin-configurable HOME link, manual text, SNS platform switches, gdgd switch, gdgd label, and basic colors
- SNS credential fields are disabled in the admin UI while each platform integration is OFF
- Admin password change

## Known Gaps

- Applying every saved `config.cgi`-equivalent setting to runtime limits
- Applying every saved `skincfg.cgi`-equivalent setting to live styling
- CSRF protection
- Production deployment packaging
- Analytics feature implementation
