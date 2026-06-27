# Document Holder Frontend

`document-holder` is now a general-purpose document holder frontend. It stores posted articles, HTML folders, and guide documents by tag and author, using the same backend storage model as `materials-library`.

## Role

- Post documents written in the browser with a Markdown-oriented editor.
- Upload a single HTML file or a folder containing `index.html` / `index.htm`.
- Package browser-written or folder-uploaded documents as ZIP files before sending them to the backend.
- Organize entries by tag and author, matching the materials-library information architecture.
- Reuse the common `material_items` API and isolated `document-holder` runtime DB/storage.

## Commands

From the repository root:

```powershell
npm run dev:document-holder
npm run build:document-holder
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
server/runtime/document-holder/database.sqlite
server/runtime/document-holder/storage/data/
```

Release ZIP runtime:

```text
database.sqlite
storage/data/
```

## Docs

- [Spec](../../docs/frontends/document-holder/SPEC.md)
- [DB](../../docs/frontends/document-holder/DB.md)

