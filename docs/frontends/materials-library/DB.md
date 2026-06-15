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
- `material_media`: MP3 preview files for audio/voice materials.
- `users`: shared login records plus `materials_author_name` and JSON `materials_default_terms`.

`material_items.user_id` is nullable. Non-null values define author identity by user ID. Null values define author identity by `author_name`; therefore an ID author and a guest author with the same name do not merge.

## Files

Archives use `material-<id>-archive.<ext>`. Preview images use `material-<id>-image.<ext>`. Replacements archive the previous file with a timestamp. Soft deletion retains all files; permanent purge removes them.

Full backup Zip includes `database.sqlite` and every file under `storage/data/`.
The admin screen uses the browser save-file picker when supported and streams the generated Zip directly to the selected destination. Packaged releases include `.user.ini` with a 1 GB upload limit so large full-backup files can be restored on compatible PHP/FastCGI hosting. If the hosting provider overrides this file, configure `upload_max_filesize` and `post_max_size` in the hosting control panel.

## Legacy Sozaiko import

The importer reads `Sozaiko.html`, archives under `zip/`, and previews under `img/`.
It copies files into the Materials Library runtime and never moves or deletes the legacy source.

```powershell
tools\import_legacy_materials.bat frontends\materials-library\legacy\05_Sozaiko --dry-run
tools\import_legacy_materials.bat frontends\materials-library\legacy\05_Sozaiko
```

Each imported row records a unique `material_items.legacy_source`. Running the command again skips existing rows.
Entries without a user ID are grouped by their legacy author name. HTML link typos are repaired when a matching preview/file stem exists, and archive files missing from the HTML are imported as supplemented entries.

The five legacy usage terms are preserved verbatim. `○` means allowed, `×` means rejected, and `△` remains unknown and displays as `?`. Rerunning the importer also resynchronizes existing imported answers.

```powershell
server\.php\php.exe tools\audit_material_terms.php
```

This audit command compares every imported answer with `Sozaiko.html`.
