# materials-library Spec

[Back to specification index](../../SPEC.md)

## Purpose

`materials-library` is the production material library frontend.

It is based on the legacy black/yellow materials page with material cards and usage-rule tables.

## Planned Behavior

- Browse and download image, effect, sound, and archive materials.
- Display author/description and usage flags per material.
- Support preview images where available.
- Keep moderation and editing compatible with the common backend.
- Keep runtime DB and storage isolated from every other frontend.

## Visual Direction

- Black background.
- Pale yellow text and section headers.
- Material cards with compact usage tables.

## Current Status

Initial React/Vite frontend exists. Material-specific data behavior is still being designed.
