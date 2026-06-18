# file-uploader Spec

[Back to specification index](../../SPEC.md)

## Purpose

`file-uploader` is the standalone file uploader frontend.

It is based on the legacy gray uploader UI and should keep that compact table-and-form feel.

## Behavior

- Upload files with comments.
- Show a HOME link in the title bar. Administrators can configure its destination.
- Show file size, date, original filename, download link, and delete action.
- Copy an uploaded file's public URL from each list row.
- Support delete-key/password style deletion.
- Reflow list rows into readable cards on narrow screens and wrap long comments and filenames.
- Paginate uploaded file rows.
- Reject disallowed extensions when a file is selected and again in the PHP API.
- Reject files over the configured size when selected and again in the PHP API.
- Provide an administrator screen for bulk deletion, restore/purge, uploader settings, analytics, design, and password changes.
- Export/import uploader settings and design independently as JSON.
- Export/import a full backup ZIP containing the frontend DB, uploaded files, settings, and design from the maintenance tab.
- Apply saved uploader colors to the live page and provide a live design preview.
- Customize page, title, table, form, input, link, primary/secondary/danger button, tab, status, message, and overlay colors independently.
- Keep older settings/design JSON compatible by filling newly added color fields from the current defaults.
- Provide a gray dark default theme plus Reset to Default and Undo Editing actions.
- Reset and undo update only the live preview; the database changes only when Save is pressed.
- Reuse the common ThreadForge backend where practical.
- Keep runtime DB and storage isolated from every other frontend.

## Visual Direction

- Gray dark uploader form and table aligned with Image Board and Materials Library.
- Simple HTML-like density.
- Minimal decoration.

## Current Status

Version 1.0.2 implements the upload/list API, configurable HOME link, delete-key deletion, administrative bulk deletion, restore/purge, settings, analytics, expanded design customization, independent JSON import/export, full backup ZIP import/export, responsive presentation, and the dedicated DB table.
