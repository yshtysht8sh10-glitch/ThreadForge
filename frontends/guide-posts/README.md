# Guide Posts Frontend

MUGEN and dot-art production guide posting frontend.

## Role

- Post guide pages or articles.
- Render a navigation-heavy information site.
- Reuse the common ThreadForge backend where possible.
- Share frontend primitives from `../shared`.

## Commands

From the repository root:

```powershell
npm run dev:guide-posts
npm run build:guide-posts
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
server/runtime/guide-posts/database.sqlite
server/runtime/guide-posts/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/guide-posts/SPEC.md)
- [DB](../../docs/frontends/guide-posts/DB.md)
