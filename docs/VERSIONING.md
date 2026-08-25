# Application Versioning

ThreadForge is a monorepo, but versions are managed independently per application. The repository itself has no release version.

## Sources of Truth

Each `frontends/<frontend-id>/package.json` is the single source of truth for that application. Use `npm run versions` to read every current version and `npm run versions:check` to validate the structure and SemVer values.

The release builder reads only that file and writes the selected version into the packaged application's `VERSION` file. The generated file is deployment metadata, not another source of truth.

## Release Rules

- Use SemVer (`MAJOR.MINOR.PATCH`) for each application.
- Bump only the application being changed or released. Never bump unrelated applications.
- Add the application name and version to its changelog heading.
- Name new tags `<app-name>-vX.Y.Z`.
- Include the application name in the GitHub Release name, for example `Proxy Release 0.9.8`.
- Do not change or delete historical tags or GitHub Releases. Old unscoped `vX.Y.Z` tags remain historical Image Board tags and must not be reused for new releases.

## Release Procedure

1. Update only `frontends/<frontend-id>/package.json` for the application being released.
2. Add that application's entry to `CHANGELOG.md` and `CHANGELOG.ja.md`.
3. Run `npm run versions:check` and the application's tests/build.
4. Run `npm run release:<frontend-id>` and verify the ZIP filename and packaged `VERSION`.
5. Create `<frontend-id>-v<version>` and a GitHub Release named `<App Name> <version>`.
