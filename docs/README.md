# ThreadForge Documentation

This directory contains documentation for the current React + PHP implementation.

## Current Structure

- `client/`: React + TypeScript + Vite SPA
- `server/`: PHP + SQLite API currently used by the app
- `server/laravel/`: Laravel migration skeleton, not used by the current runtime
- `docs/`: project documentation
- `ThreadForge_Spec.md`: current product specification

Legacy CGI files and old data directories are migration/reference material. They are not required to run the current app.

## Implemented Features

- Thread list and thread detail views
- New post creation with image upload
- Reply creation without Tweet or image controls
- "ええじゃないか" fixed-comment replies
- Up to 10 replies shown on the top page
- Edit and soft-delete flows
- Admin restore for soft-deleted posts
- Admin bulk delete without per-post passwords
- Admin DB integrity check
- Admin backup export/import for DB, images, and settings
- Search by title, body, and author
- RSS output
- Tweet text generation for new top-level posts
- Tweet OFF and gdgd visual modes
- Old-board-style top navigation and board layout
- Embedded manual page

## Not Yet Fully Implemented

- Applying saved admin `config.cgi`-equivalent settings to all runtime limits
- Applying saved admin `skincfg.cgi`-equivalent settings to live styling
- CSRF protection
- Full legacy CGI data migration tooling
- Production deployment packaging

## Documentation Index

- `../ThreadForge_Spec.md`: product specification
- `ARCHITECTURE.md`: system architecture
- `API.md`: PHP API reference
- `DB.md`: SQLite schema and storage notes
- `TESTING.md`: test strategy
