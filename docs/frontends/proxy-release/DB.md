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
- the shared Bearer Token is stored as `security.webMugenApiToken` in the existing `settings`
  table; administrator reads expose only a `tokenConfigured` flag, never the plaintext
- `config.webMugenApiUrl` and `config.webMugenStageId` store the same-host endpoint and Stage
  selection used for publication
- `config.proxyTrialPlayButtonsEnabled` controls whether item-specific trial-play buttons are
  shown publicly; it does not alter `webmugen_play_url`
- future schema may add release metadata if title/message/settings are not enough

## Isolation Rule

`proxy-release` must not read or write another frontend's DB or storage directory.
