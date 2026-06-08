# image-board Frontend

Current production image posting board frontend.

## Role

- Image posting board with top-level posts and replies.
- User login, profile icons, edit/delete, search, ranking, manual, and admin settings.
- SNS posting support for enabled platforms.
- Uses the common ThreadForge backend with frontend id `image-board`.

## Commands

From the repository root:

```powershell
npm run dev:image-board
npm run test:image-board
npm run build:image-board
```

Directly in this directory:

```powershell
npm install
npm run dev
npm test -- --run
npm run build
```

## Runtime Data

Development runtime:

```text
server/runtime/image-board/database.sqlite
server/runtime/image-board/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/image-board/SPEC.md)
- [DB](../../docs/frontends/image-board/DB.md)
