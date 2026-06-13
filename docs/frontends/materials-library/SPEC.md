# Materials Library Specification

## Concept

Materials Library is a file-posting application optimized for preservation and discovery. The downloadable archive is the primary asset; preview images explain the archive. Unlike Image Board, entries are grouped by purpose/tag and author instead of social posting order.

## Navigation

- HOME: opens the administrator-configured URL.
- List: grouped material catalog and table of contents.
- Post: registers an archive, preview image, metadata, and terms answers.
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

## Posting

Required fields are material name, author name, tag, archive, and post password. A preview image and notes are optional. Every configured usage term is answered with yes/no and stored with the item.

Logged-in users receive defaults from their material profile:

- author name
- icon
- yes/no answers for each current usage term

## Administration

- Passwordless bulk soft delete after admin authentication
- Restore and permanent purge
- Author ID assignment
- Full DB/storage backup and restore
- Material count, author count, tag count, storage size, and monthly analytics
- Title, description, HOME, manual, grouping order, upload limits, and extensions
- Tag and usage-term add/edit/delete
- Color design settings
- Admin password initialization and change

## Release

Version 1.0.0 deploys under `15_materials_library/`. Runtime DB and uploaded files are not bundled in the release Zip.
