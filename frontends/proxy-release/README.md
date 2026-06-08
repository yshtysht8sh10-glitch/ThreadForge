# Proxy Release Frontend

MUGEN character/plugin proxy release frontend.

## Role

- Publish proxy-hosted plugin or character releases.
- Display release descriptions and downloadable assets.
- Reuse the common ThreadForge backend where possible.
- Share frontend primitives from `../shared`.

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
