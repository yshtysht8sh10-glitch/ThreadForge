# proxy-release Spec

[Back to specification index](../../SPEC.md)

## Purpose

`proxy-release` is a ThreadForge frontend for proxy-hosted MUGEN character releases.
It uses the materials-library data model because its main workflow is also to collect
uploaded archives and organize them by tag and author.

## Current Behavior

- Uses the shared material item API and runtime tables.
- Accepts archive uploads, with `zip` as the proxy-release default.
- When a MUGEN character zip is selected, the frontend attempts to read `.air` and
  `.sff` files in the browser and generate an idle-motion GIF preview.
- A manually selected explanation image overrides the generated GIF.
- HOME, list, post, delete, edit, manual, login, and admin screens follow the
  materials-library workflow.
- After a proxy-release create or archive-changing update is committed, the server calls
  WebMUGEN's authenticated Catalog endpoint with the publication ID, actual stored ZIP
  basename, and configured Stage ID. The publication ID controls stable Character identity;
  the archive basename only selects a file below WebMUGEN's fixed proxy storage root.
- A successful registration stores and renders the item-specific WebMUGEN play URL on the
  release card. A failed registration preserves the published item and records a structured
  error so publication success is not confused with trial-play readiness.
- The archive action is labeled `Download` and uses a filled accent-color style as the card's
  primary action. Administrators can hide every item-specific trial-play button without
  deleting the stored URLs; the display setting defaults to enabled.
- The navigation includes a `Trial Play` link to the right of the login item. The
  destination is configurable from the admin settings screen. If no URL is set, the
  item is displayed as disabled instead of linking to a fallback page.

## Visual Direction

- The default color palette follows materials-library's gray theme.
- The list card body keeps the materials-library layout, but the card outer border is
  removed and the preview image can overflow outside the card body.
- A green legacy skin can be configured through the design settings, but it is not the
  default.

## Notes

- The automatic GIF generator currently targets SFF v1/PCX sprites, matching the
  WebMUGEN parser support used as the source implementation.
- If AIR/SFF parsing fails, users can still upload a manual explanation image.
- The administrator settings screen stores the WebMUGEN API Token in the server-side SQLite
  `security` settings and never reads the saved plaintext back into the browser. It also stores
  the API URL and Stage ID. The URL is restricted to the current host and a path ending in
  `/api/catalog.php`; the action query is supplied by the server.
- `THREADFORGE_WEBMUGEN_CATALOG_SECRET` and `THREADFORGE_WEBMUGEN_CATALOG_API_URL` remain
  server-only fallbacks for existing deployments. Neither value is part of public settings.
- Server-to-server Catalog requests send the configured Token in both the standard
  `Authorization: Bearer ...` header and the rental-hosting-compatible
  `X-WebMUGEN-Token: ...` header. The Token is never added to the JSON request body.
- The administrator `Data Editor` can search existing publications, edit the normal publication
  fields, inspect actual stored archive metadata, manually save an HTTP(S) trial-play URL, and
  explicitly upsert one publication into WebMUGEN.
- The maintenance screen can upsert every published, non-deleted publication whose trial-play URL
  is empty. One failed item does not stop later items, and the result reports target, success, and
  failure counts with per-item errors.
- Both individual and bulk registration send the stable publication ID on every attempt. A retry
  intentionally calls the Catalog API again so an updated ZIP replaces `proxy-release-<id>`.
- Public APIs and cards expose a trial-play link only when `webmugen_play_url` is present. Stored
  paths, Character IDs, and structured registration errors remain administrator-only.
