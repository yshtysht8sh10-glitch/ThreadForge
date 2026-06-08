# file-uploader Spec

[Back to specification index](../../SPEC.md)

## Purpose

`file-uploader` is the standalone file uploader frontend.

It is based on the legacy gray uploader UI and should keep that compact table-and-form feel.

## Planned Behavior

- Upload files with comments.
- Show file size, date, original filename, download link, and delete action.
- Support delete-key/password style deletion.
- Paginate uploaded file rows.
- Reuse the common ThreadForge backend where practical.
- Keep runtime DB and storage isolated from every other frontend.

## Visual Direction

- Gray uploader form and table.
- Simple HTML-like density.
- Minimal decoration.

## Current Status

Initial React/Vite frontend exists. Backend behavior is still being adapted from the common board model.
