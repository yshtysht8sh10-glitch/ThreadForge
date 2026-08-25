# Changelog

[Japanese changelog](CHANGELOG.ja.md)

Each ThreadForge application follows semantic versioning independently. Entries without an application name below are historical Image Board entries and are now labeled accordingly; no historical release or tag was changed.

## Proxy Release 0.9.8 - 2026-08-25

- Added category-aware WebMUGEN Character/Stage publication and configurable default Character selection for Stage trial URLs.
- Corrected the deployed WebMUGEN API path casing and shortened upstream HTML errors in the administrator UI.

## Document Holder 0.9.7 - 2026-06-28

- Added the standalone Document Holder frontend for articles, HTML folders, and guide documents.

## Proxy Release 0.9.7 - 2026-06-21

- Added proxy-release release packaging through `npm run release:proxy-release`
- Moved the Trial Play navigation item to the right of login and disabled it visually when no URL is configured
- Documented proxy-release runtime behavior, release packaging, legacy-style tags, usage terms, and trial-play settings

## File Uploader 1.0.2 - 2026-06-17

- Expanded file-uploader design settings to cover title and table text, cells, labels, inputs, placeholders, button variants, tabs, status messages, admin messages, links, and the admin overlay while retaining old JSON compatibility
- Replaced the old pale file-uploader default colors with a darker gray ThreadForge theme and remapped the legacy light default palette to the new default
- Removed the fixed legacy `count:80747` display because it was not connected to any live counter

## Materials Library 0.9.2 - 2026-06-16

- Required post-password entry and verification for all material edits and deletes, including the logged-in owner

## Materials Library 0.9.1 - 2026-06-15

- Added Materials Library admin controls for parent-site SSO enablement, shared-secret editing, generation, and copy
- Disabled local registration and ThreadForge-side user edits or erasure while SSO is enabled
- Added registered-user administration for material profiles, icons, login passwords, and two-stage erasure
- Preserved material author references when permanent user deletion compacts user IDs
- Sent settings as Base64 to avoid hosting WAF rejection during save
- Improved full-backup restore with material-archive rejection, readable HTML-server errors, and post-restore admin reauthentication
- Moved user administration under Maintenance and added a confirmation before opening it
- Added restore-stage status, elapsed time, progress animation, and duplicate-submit prevention during full-backup restore

## Materials Library 0.9.0 - 2026-06-14

- Rebuilt Materials Library as an archive-first catalog grouped by tag and author
- Added archive and preview-image posting with administrator-managed yes/no usage terms
- Added edit, soft delete, admin bulk delete, restore, permanent purge, analytics, settings, and design screens
- Added local login, parent-site SSO, material author profiles, icons, and default term answers
- Kept logged-in author IDs separate from same-name guest/imported authors and added admin author-ID assignment
- Added an idempotent legacy `05_Sozaiko` importer for archives, previews, authors, categories, notes, and usage terms
- Added isolated SQLite tables, storage, full backup support, HTTP integration tests, and `15_materials_library` release packaging
- Added gray-based default colors, author index links, compact author rows, aligned card fields, and a fixed Back to Top action
- Added multiple MP3 previews for audio/voice materials and audio migration from the legacy library
- Normalized imported usage terms to the five legacy fields and added resynchronization/auditing that preserves `△` as unknown
- Unified design reset and undo across Image Board, File Uploader, and Materials Library as unsaved live previews
- Added file-dialog based settings/design JSON transfer and expanded theme colors while retaining old JSON compatibility
- Switched uneven material cards to a compact column layout without row-height gaps
- Restricted passwordless and legacy materials to admin-only edit and delete operations
- Added required-field markers, the `blank` name default, yes-by-default terms, saved user term defaults, and delete/edit indexes
- Limited Back to Top to list, delete, and edit screens
- Simplified registration to login ID, password, and confirmation, with a password visibility toggle
- Fixed large full-backup export with save-file picker streaming, stored compression for existing archives/media, and packaged PHP upload limits for restore

## File Uploader 1.0.0 - 2026-06-12

- Completed the standalone file upload, list, URL copy, delete-key deletion, and responsive mobile flows
- Added administrator bulk delete, restore/purge, settings, analytics, design customization, and password management
- Added settings/design JSON import/export and full DB/file backup ZIP import/export
- Added frontend and HTTP integration regression tests for uploader validation and lifecycle operations
- Added frontend-selectable release packaging and the `threadforge-file-uploader-1.0.0.zip` artifact
- Rebuilt the documentation as colored bilingual HTML with an in-page Japanese/English switch
- Added a HOME link to file-uploader with an administrator-configurable destination
- Fixed uploaded file URLs when file-uploader is deployed in a subdirectory
- Added a gray dark layout aligned with the other frontends and unsaved design reset/undo previews

## Image Board 0.9.7 - 2026-06-07

- Displayed replies below the post edit form for editing the body in conversation context
- Reused the standard list reply and analytics layout without duplicating the parent post
- Displayed every reply on the edit screen without the normal ten-reply preview limit

## Image Board 0.9.6 - 2026-06-07

- Made the thread list the universal entry route with or without login
- Added SSO token processing directly to the thread list route

## Image Board 0.9.5 - 2026-06-07

- Processed parent-site SSO tokens even when a previous ThreadForge session already exists
- Ensured re-entry from the parent site always replaces the user settings route with the thread list
- Added regression coverage for SSO re-entry with an existing session

## Image Board 0.9.4 - 2026-06-07

- Fixed the HashRouter transition after SSO login so the thread list is shown first
- Removed direct browser history manipulation that could leave the user settings route active

## Image Board 0.9.3 - 2026-06-07

Account experience maintenance release.

- Redirected successful parent-site SSO logins to the thread list
- Added icon selection to new account registration with immediate preview
- Updated the current icon immediately when selecting a replacement in user settings
- Added regression coverage for SSO navigation and icon previews

## Image Board 0.9.2 - 2026-06-07

Administration and SSO maintenance release.

- Disabled ThreadForge-side registered-user editing and deletion while parent-site SSO is enabled
- Added secure shared-secret generation and clipboard copy controls to the SSO settings
- Added clearer guidance that SSO-managed user information must be maintained on the parent site
- Improved registered-user administration, including icon upload handling and related validation
- Expanded automated coverage for SSO restrictions and administration controls

## Image Board 0.9.1 - 2026-06-04

Maintenance release for the production board.

- Replaced the display-period open/close control with a compact triangle button for both desktop and mobile layouts
- Kept the display-period triangle visible in compact mobile layouts
- Improved mobile wrapping of SNS reaction/status metrics so counters fit more safely on narrow screens
- Hid Bluesky/Mastodon/Misskey destination rows for posts created with SNS transfer disabled
- Disabled local ThreadForge account registration while SSO is enabled and added guidance to create accounts on the parent site
- Allowed keyed web execution of `cron.php` for rental-server cron jobs while keeping keyless browser access blocked
- Improved SNS reaction logs so successful entries containing empty `error` fields are not highlighted as errors

## Image Board 0.9.0 - 2026-05-31

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

## Image Board 0.1.0 - 2026-05-05

Initial public repository baseline.

- React + TypeScript frontend
- PHP + SQLite backend
- Thread list, posting, replies, search, edit, and soft delete
- Admin restore, bulk delete, integrity check, backup export/import, and settings storage
- Embedded manual and project documentation
