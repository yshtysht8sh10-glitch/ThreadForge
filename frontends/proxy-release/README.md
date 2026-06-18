# Proxy Release Frontend

MUGEN character proxy release frontend.

## Role

- Publish proxy-hosted MUGEN character releases.
- Reuse the materials-library item model for archive uploads, terms, authors, tags, editing, deletion, login, and admin.
- Generate an idle-motion GIF from AIR/SFF files in a selected character zip when possible.
- Allow a manual explanation image to override the generated preview.
- Provide an admin-configurable trial-play link in the top navigation.

## Commands

From the repository root:

```powershell
npm run dev:proxy-release
npm run build:proxy-release
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
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/proxy-release/SPEC.md)
- [DB](../../docs/frontends/proxy-release/DB.md)
