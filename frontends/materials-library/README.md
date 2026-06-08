# Materials Library Frontend

Production material library frontend.

## Role

- Browse and download image, effect, sound, and archive materials.
- Display license and usage flags per material.
- Reuse the common ThreadForge backend where possible.
- Share frontend primitives from `../shared`.

## Commands

From the repository root:

```powershell
npm run dev:materials-library
npm run build:materials-library
```

Directly in this directory:

```powershell
npm install
npm run dev
npm run build
```

## Runtime Data

Development runtime:

```text
server/runtime/materials-library/database.sqlite
server/runtime/materials-library/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/materials-library/SPEC.md)
- [DB](../../docs/frontends/materials-library/DB.md)
