# Materials Library Database

Frontend ID: `materials-library`

## Runtime

```text
server/runtime/materials-library/database.sqlite
server/runtime/materials-library/storage/data/
```

Packaged deployment:

```text
15_materials_library/database.sqlite
15_materials_library/storage/data/
```

## Tables

- `material_items`: archive/image paths, metadata, owner user ID, password hash, timestamps, and soft-delete state.
- `material_tags`: administrator-managed purpose categories and ordering.
- `material_terms`: administrator-managed usage terms and descriptions.
- `material_item_terms`: yes/no answer snapshot for each item and term.
- `users`: shared login records plus `materials_author_name` and JSON `materials_default_terms`.

`material_items.user_id` is nullable. Non-null values define author identity by user ID. Null values define author identity by `author_name`; therefore an ID author and a guest author with the same name do not merge.

## Files

Archives use `material-<id>-archive.<ext>`. Preview images use `material-<id>-image.<ext>`. Replacements archive the previous file with a timestamp. Soft deletion retains all files; permanent purge removes them.

Full backup Zip includes `database.sqlite` and every file under `storage/data/`.
