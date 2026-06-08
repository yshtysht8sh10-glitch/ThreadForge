# ThreadForge Frontends

ThreadForge is being reorganized into multiple frontend applications that share one backend.

The backend code is shared, but runtime data is not. Each frontend should run with its own `THREADFORGE_FRONTEND_ID`, which gives it a separate SQLite DB and uploaded-file storage directory during development.

Development runtime files:

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

Release ZIP runtime files:

```text
database.sqlite
storage/data/
```

## Frontend Slots

- `image-board/`: Current image posting board frontend. See `image-board/README.md`.
- `file-uploader/`: File uploader frontend. See `file-uploader/README.md`.
- `guide-posts/`: MUGEN and dot-art production guide posting frontend. See `guide-posts/README.md`.
- `proxy-release/`: MUGEN character proxy release frontend. See `proxy-release/README.md`.
- `materials-library/`: Production material library frontend. See `materials-library/README.md`.

## Commands

From the repository root:

```powershell
npm run dev:image-board
npm run dev:file-uploader
npm run dev:guide-posts
npm run dev:proxy-release
npm run dev:materials-library
npm run test:image-board
npm run build:image-board
npm run build:frontends
npm run release:image-board
```

Backend examples:

```powershell
$env:THREADFORGE_FRONTEND_ID = 'image-board'
php -S 127.0.0.1:8000 -t server
```

```powershell
$env:THREADFORGE_FRONTEND_ID = 'file-uploader'
php -S 127.0.0.1:8001 -t server
```

For direct frontend work:

```powershell
cd frontends/image-board
npm install
npm run dev
npm test -- --run
npm run build
```

## Shared Frontend Code

Reusable UI, hooks, types, and API clients should move to `shared/` when at least two frontends need them.

Do not make frontend-specific styling or page flow depend on another frontend directly. Share small pieces through `shared/` instead.

## Adding a Frontend

1. Create the new app under `frontends/<name>/`.
2. Keep its routes, pages, CSS, and copy local to that app.
3. Add `frontends/<name>/README.md`.
4. Add `docs/frontends/<name>/README.md`, `DB.md`, and `SPEC.md`.
5. Add `docs/ja/frontends/<name>/README.md`, `DB.md`, and `SPEC.md`.
6. Use the common backend through a local API adapter.
7. Move only stable, reused pieces into `shared/`.
8. Add build and release scripts explicitly when the frontend becomes releasable.

## Legacy References

The new non-image-board frontends keep their source site snapshots under `legacy/`.

- `file-uploader/legacy/`: gray PHP uploader source and uploaded file archive
- `guide-posts/legacy/`: Do||Mu,File static HTML/CSS guide site
- `proxy-release/legacy/`: dark green proxy release site and character assets
- `materials-library/legacy/`: black/yellow material library and asset archive

Keep `legacy/` as reference material. Build new behavior in `src/` and move reusable pieces to `shared/`.
