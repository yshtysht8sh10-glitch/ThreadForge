# proxy-release DB

[Back to DB index](../../DB.md)

Frontend id: `proxy-release`

## Runtime Files

Development:

```text
server/runtime/proxy-release/database.sqlite
server/runtime/proxy-release/storage/data/
```

Release ZIP deployment:

```text
database.sqlite
storage/data/
```

## Current Schema

This frontend currently uses the common ThreadForge SQLite schema initialized by `server/db.php`.

The expected data shape is release-oriented:

- each top-level post represents a proxy release entry
- downloadable packages and images are stored under this frontend's storage
- `webmugen_character_id` stores the stable Catalog ID returned after server-side ZIP validation
- `webmugen_play_url` stores the returned item-specific trial-play URL
- `webmugen_error` stores the latest structured registration failure and is cleared on success
- `webmugen_access_key` stores the 32-character lowercase hexadecimal access key generated for unlisted publications; it is never exposed by public APIs
- the shared Bearer Token is stored as `security.webMugenApiToken` in the existing `settings`
  table; administrator reads expose only a `tokenConfigured` flag, never the plaintext
- `config.webMugenApiUrl` and `config.webMugenStageId` store the same-host endpoint and Stage
  selection used for publication
- `config.proxyTrialPlayButtonsEnabled` controls whether item-specific trial-play buttons are
  shown publicly; it does not alter `webmugen_play_url`
- future schema may add release metadata if title/message/settings are not enough

## Isolation Rule

`proxy-release` must not read or write another frontend's DB or storage directory.
## Test publication lifecycle

`material_items.publication_type` (`normal` or `test`) and `material_items.visibility` (`public` or `unlisted`) are independent fields. Existing rows default to `normal + public`; rows that were already test publications when the visibility column is introduced are migrated to `test + unlisted`. New test publications use `test + unlisted` and receive a unique 128-bit `webmugen_access_key` on first WebMUGEN registration. Promotion updates the same row to `normal + public`, clears the expiry and memo, and retains its files, access key, and WebMUGEN stable ID. This separation permits future combinations such as `normal + unlisted` without overloading lifecycle state. Expired test rows are hidden immediately; cleanup removes the opaque WebMUGEN Catalog entry first and then deletes the row, ZIP, generated image, and media. A remote deletion failure retains the hidden local row and access key for retry.
