# Frontend Architecture

ThreadForge is moving toward one common backend with multiple frontend applications. The current image posting board is now treated as the first frontend, not as the only frontend.

## Current Layout

```text
frontends/
  image-board/          Current production image posting board frontend
  file-uploader/        File uploader frontend
  guide-posts/          Guide/article posting frontend
  proxy-release/        MUGEN proxy release frontend
  materials-library/    Materials library frontend
  shared/               Shared frontend code
server/                 Common PHP backend and SQLite storage
```

`frontends/image-board` is the existing production application moved from the former `client/` directory.
The other frontend directories now contain first-pass React/Vite applications based on their `legacy/` site materials.

## Target Shape

- Keep `server/` as the common backend for authentication, posts, replies, files, analytics, SSO, and settings.
- Keep backend code common, but keep DB files and uploaded-file storage separated by frontend id during development.
- Keep frontend-specific page flow, layout, skin, wording, and route trees inside each frontend directory.
- Put shared UI, hooks, types, and API helpers in `frontends/shared/` only after at least two frontends need the same code.
- Build and release one frontend at a time. The release script accepts a frontend id and packages that frontend with the common backend.
- Prefer small adapters over backend forks when a frontend needs different labels or presentation.

## Runtime Isolation

Each frontend should run with its own `THREADFORGE_FRONTEND_ID` during development. Development runtime data is stored under:

```text
server/runtime/<frontend-id>/database.sqlite
server/runtime/<frontend-id>/storage/data/
```

When a release ZIP is built, it is packaged as a single frontend app. Inside that ZIP, the same backend stores data locally:

```text
database.sqlite
storage/data/
```

This means `image-board` and `file-uploader` do not share posts, users, settings, sessions, or uploaded files while developing locally. The release artifact for each frontend is also standalone.

## Frontends

- `image-board`: the current ThreadForge image posting board.
- `file-uploader`: a file upload list with comments, size metadata, delete keys, and paging.
- `guide-posts`: an article-style posting site for MUGEN and dot-art production knowledge.
- `proxy-release`: a proxy release site for MUGEN characters, plugins, or related packages.
- `materials-library`: a browseable material library with downloads and usage flags.

## Legacy Migration

- Each new frontend keeps the source legacy site in `legacy/` for visual and content reference.
- The first-pass React apps intentionally copy the color palette and broad layout from legacy while using modern components and Vite builds.
- `file-uploader` keeps the gray table/upload-form feel.
- `guide-posts` keeps the white page, fixed teal header, and left menu/article layout.
- `proxy-release` keeps the dark background and bright green text.
- `materials-library` keeps the black background, pale yellow text, and material cards with rule tables.
- Legacy assets can be imported into React with `new URL('../legacy/...', import.meta.url).href` when a screen needs representative images.

## Working Rules

1. Start new frontend work under its own `frontends/<name>/` directory.
2. Do not import directly from another concrete frontend.
3. If duplication appears, copy locally first; move to `frontends/shared/` when reuse is proven.
4. Keep common backend contracts documented in `docs/SPEC.md`.
5. Keep release scripts explicit about which frontend they package.

Release examples:

```powershell
npm run release:image-board
npm run release:file-uploader
```

## Versioning

- Each frontend has its own semantic version. `image-board` and `file-uploader` do not need matching versions.
- Treat each `frontends/<frontend-id>/package.json` as the application version source and include that version in its release ZIP name.
- Prefix Git tags with the frontend id, for example `image-board-v0.9.7` and `file-uploader-v0.1.0`.
- Keep the root version for workspace-level development only, not as the release number for every standalone app.
- Bundle the common backend with each application release. Track a separate API compatibility version only when a breaking backend contract change is introduced.

This keeps the current board recoverable while allowing each new board type to grow its own personality.
