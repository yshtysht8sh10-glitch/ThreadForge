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
- The navigation includes a `Trial Play` link. The destination is configurable from
  the admin settings screen.

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
