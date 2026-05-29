# Changelog

[Japanese changelog](CHANGELOG.ja.md)

ThreadForge follows semantic versioning while it is being prepared for public use.

## 0.9.0 - 2026-05-29

Near-complete pre-public-operation release.

- Added rental-server release ZIP packaging, first-run DB creation, and full backup ZIP support
- Added non-destructive BBSNote/local archive import and created-at based post renumbering
- Added login, user settings, icons, user work lists, claimed works, and parent-site SSO
- Added year/month filters and incremental loading to list, search, bulk delete, restore/purge, and edit flows
- Reworked admin bulk delete, restore/purge, user administration, maintenance, board settings, and board design screens
- Improved board design with live preview, color sample coverage, and quick reaction settings
- Added analytics, ranking, access counter, view counts, quick reaction counts, and SNS reaction aggregation
- Added X/Bluesky/Mastodon/Misskey settings, SNS reaction refresh, and Cron/API refresh URLs
- Improved revision display, image replacement previews, search result thumbnails, and links back to list positions
- Strengthened validation, authentication, admin password setup/change, and user password reset flows
- Updated Japanese documentation, API references, operation notes, and release instructions

## 0.1.0 - 2026-05-05

Initial public repository baseline.

- React + TypeScript frontend
- PHP + SQLite backend
- Thread list, posting, replies, search, edit, and soft delete
- Admin restore, bulk delete, integrity check, backup export/import, and settings storage
- Embedded manual and project documentation
