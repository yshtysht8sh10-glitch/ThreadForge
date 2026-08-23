# Proxy Release Frontend

MUGEN character proxy release frontend.

## Role

- Publish proxy-hosted MUGEN character releases.
- Reuse the materials-library item model for archive uploads, terms, authors, tags, editing, deletion, login, and admin.
- Generate an idle-motion GIF from AIR/SFF files in a selected character zip when possible.
- Allow a manual explanation image to override the generated preview.
- Register a successfully published character ZIP with WebMUGEN and display the returned per-release trial-play link.
- Provide an admin-configurable general trial-play link in the top navigation.

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

## WebMUGEN integration

Open the proxy-release administrator screen and configure:

```text
WebMUGEN API URL=https://host/DotoEita/50_WEBMUGEN/api/catalog.php
WebMUGEN Stage ID=cyber
WebMUGEN API Token=<same server-only Bearer token as WebMUGEN>
```

The Token is stored under the SQLite server-side `security` settings. Its plaintext is not returned by either the administrator settings API or the public settings API; the form only reports whether a Token is configured. The environment variables `THREADFORGE_WEBMUGEN_CATALOG_SECRET` and `THREADFORGE_WEBMUGEN_CATALOG_API_URL` remain server-side fallbacks for existing deployments.

After the proxy-release database transaction commits, the API sends the publication ID, the basename of the actual stored ZIP, and the configured Stage ID to WebMUGEN. A successful response stores the returned Character ID and `?character=...&stage=...` URL on the item. Registration failure does not roll back or hide the proxy publication; the card shows `試遊登録失敗`, and the stored structured error remains available for retry/diagnosis.

## Docs

- [Spec](../../docs/frontends/proxy-release/SPEC.md)
- [DB](../../docs/frontends/proxy-release/DB.md)
