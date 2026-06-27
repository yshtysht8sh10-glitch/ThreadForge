# document-holder / Document Holder DB

[Back to DB index](../../DB.md)

Frontend id: `document-holder`

## Runtime Files

Development:

```text
server/runtime/document-holder/database.sqlite
server/runtime/document-holder/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Tables

`document-holder` uses the common SQLite schema initialized by `server/db.php`.

- `material_items`: stores one article per row.
- `material_tags`: stores primary and secondary tags. Secondary tags use `parent_id` to point at their primary tag.
- `material_terms`: stores reuse/license questions such as reprint, quotation, and modification.
- `users`: stores author names, default material terms, and the default TOC grouping mode.
- `settings`: stores the title, description, description banners, and design settings.

## Article Data

- `archive_path` points to an article ZIP containing `index.html`.
- `notes` stores the display HTML or a short summary.
- `draft = 1` marks an article as a draft.
- `view_count` stores article views and increments when the article page is opened.

## Legacy Migration

`tools/migrate_document_holder_runtime.php` imports old `04_DMF` HTML, rewrites image references so the articles remain readable, creates article ZIP files, and stores them under `storage/data/`.

Old categories are mapped to secondary tags under the `指南` primary tag. Article dates are taken from source HTML file modification times.

## Isolation Rule

`document-holder` must not read or write another frontend's DB or storage directory.
