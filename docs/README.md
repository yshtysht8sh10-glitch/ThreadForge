# ThreadForge Docs

This directory is the documentation index. Keep the root `README.md` as the short repository entry point, and keep detailed operational notes here.

## Canonical Documents

- `../ThreadForge_Spec.md`: current product specification and implementation status
- `../CHANGELOG.md`: release history
- `API.md`: PHP API actions and request/response notes
- `DB.md`: SQLite schema, runtime data, backups, imports, and reset procedures
- `MIGRATION.md`: old BBSnote log import notes
- `ARCHITECTURE.md`: frontend/backend structure
- `TESTING.md`: test commands and coverage notes

## Current Runtime Features

- Thread list and detail pages
- Top-level posts with images
- Replies without Tweet or image controls
- Fixed-comment "ええじゃないか" replies
- Top page preview of up to 10 replies per thread
- Thread-only display numbers with reply child numbers
- Edit and soft delete
- Admin restore and passwordless bulk delete
- Admin DB integrity check
- Admin backup export/import
- Non-destructive old BBSnote log import
- Search by all fields, title, body, or author
- RSS output
- Tweet integration is available but disabled by default for local-first operation
- Tweet OFF and gdgd visual modes
- Embedded manual page
- Admin-configurable HOME link, manual text, Tweet switch, gdgd switch, and gdgd label
- Tweet credential fields are disabled in the admin UI while Tweet integration is OFF
- Admin password change

## Known Gaps

- Applying every saved `config.cgi`-equivalent setting to runtime limits
- Applying every saved `skincfg.cgi`-equivalent setting to live styling
- CSRF protection
- Production deployment packaging
