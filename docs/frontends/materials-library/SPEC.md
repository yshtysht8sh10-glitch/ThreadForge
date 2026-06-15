# Materials Library Specification

## Concept

Materials Library is a file-posting application optimized for preservation and discovery. The downloadable archive is the primary asset; preview images explain the archive. Unlike Image Board, entries are grouped by purpose/tag and author instead of social posting order.

## Navigation

- HOME: opens the administrator-configured URL.
- List: grouped material catalog and table of contents.
- Post: registers an archive, preview image or preview MP3 files, metadata, and terms answers.
- Delete / Edit: operates on one selected material using ownership or a post password.
- Manual: displays administrator-configured text.
- Login: local account, SSO, and material-author preferences.
- Admin: also opens from the unlabelled square at the right edge of the menu.

## Catalog And Identity

- The administrator chooses `tag -> author` or `author -> tag`.
- Logged-in entries group by immutable user ID. The author name is a display label.
- Guest and imported entries group by author name because they have no user ID.
- A logged-in author and a guest author with the same displayed name remain separate.
- Administrators can assign or remove a user ID on an existing material.
- The table of contents links both tags and authors.
- Material cards use four columns on wide screens and place the next author group into available columns while preserving author grouping.

## Cards And Audio

- Card order is preview, material name, download, size/category, usage terms, and notes.
- Images retain their aspect ratio. Long material and archive names use ellipsis with full text in a tooltip.
- A newly added usage term without an answer displays `?`.
- Audio/voice categories accept multiple MP3 preview files and show a separate player for each file.
- Back to Top appears at the lower right on list, delete, and edit screens.

## Posting

The material name starts as `blank`. Author name, file, and post password are marked required with a red `*`. A preview image and notes are optional. Every usage term defaults to yes.

Logged-in users receive defaults from their material profile:

- author name
- icon
- yes/no answers for each current usage term

Delete and edit screens include the same tag/author index as the catalog.

Registration requires only a login ID, login password, and password confirmation. Author name and post password are configured after login. Login and registration can toggle password visibility.

## SSO

- Admin settings can enable or disable parent-site SSO and configure, generate, or copy the shared secret.
- Enabling SSO disables local account registration so account creation remains on the parent site.
- Enabling SSO also disables ThreadForge-side user edits, information erasure, and permanent user-number deletion.
- Existing local login and the signed-in Materials Library profile remain available while SSO is enabled.

## Administration

- Passwordless bulk soft delete after admin authentication
- Materials without a post password, including legacy imports, can only be changed from the admin screen
- Restore and permanent purge
- Author ID assignment
- Registered-user list and management for login ID, shared display name, material author name, post password, HOME, term defaults, login password, and icon
- Two-stage user erasure, with material author IDs kept consistent when user numbers are compacted
- Full DB/storage backup and restore
- Material count, author count, tag count, storage size, and monthly analytics
- Title, description, HOME, manual, grouping order, upload limits, and extensions
- Tag and usage-term add/edit/delete
- Gray-based default colors with administrator controls for headings, navigation, inputs, button variants, image areas, muted text, and usage-term symbols
- Reset and undo update the live preview immediately but persist only after Save
- Import and export board settings and design through file dialogs as separate, typed JSON files
- Preserve compatibility with older design JSON by filling newly added colors from defaults
- Admin password initialization and change

## Release

Version 0.9.0 is the first public preview release and deploys under `15_materials_library/`. Runtime DB and uploaded files are not bundled in the release Zip.
