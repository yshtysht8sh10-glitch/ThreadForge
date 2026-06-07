# Changelog

[Japanese changelog](CHANGELOG.ja.md)

ThreadForge follows semantic versioning while it is being prepared for public use.

## 0.9.2 - 2026-06-07

Administration and SSO maintenance release.

- Disabled ThreadForge-side registered-user editing and deletion while parent-site SSO is enabled
- Added secure shared-secret generation and clipboard copy controls to the SSO settings
- Added clearer guidance that SSO-managed user information must be maintained on the parent site
- Improved registered-user administration, including icon upload handling and related validation
- Expanded automated coverage for SSO restrictions and administration controls

## 0.9.1 - 2026-06-04

Maintenance release for the production board.

- Replaced the display-period open/close control with a compact triangle button for both desktop and mobile layouts
- Kept the display-period triangle visible in compact mobile layouts
- Improved mobile wrapping of SNS reaction/status metrics so counters fit more safely on narrow screens
- Hid Bluesky/Mastodon/Misskey destination rows for posts created with SNS transfer disabled
- Disabled local ThreadForge account registration while SSO is enabled and added guidance to create accounts on the parent site
- Allowed keyed web execution of `cron.php` for rental-server cron jobs while keeping keyless browser access blocked
- Improved SNS reaction logs so successful entries containing empty `error` fields are not highlighted as errors

## 0.9.0 - 2026-05-31

First production operation release.

- Added rental-server release ZIP packaging, first-run DB creation, and full backup ZIP support
- Added non-destructive BBSNote/local archive import, multi-folder import, image repair, and recent imported post update tools
- Added `--add` / `add=1` support for inserting unmatched latest archive entries during recent imported post updates
- Refreshed existing imported post image files during recent update runs so changed BBSNote images can be reflected without re-importing everything
- Kept post renumbering from renaming image files unexpectedly, with image repair available when old archive images need to be reattached
- Added login, user settings, icons, user work lists, claimed works, and parent-site SSO
- Added year/month filters and incremental loading to list, search, bulk delete, restore/purge, and edit flows
- Reworked admin bulk delete, restore/purge, user administration, maintenance, board settings, and board design screens
- Improved board design with live preview, color sample coverage, and quick reaction settings
- Added analytics, ranking, access counter, view counts, quick reaction counts, and SNS reaction aggregation
- Added X/Bluesky/Mastodon/Misskey settings, SNS reaction refresh, and Cron/API refresh URLs
- Switched Mastodon media upload to `/api/v1/media` and added debug logging for production investigation
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
