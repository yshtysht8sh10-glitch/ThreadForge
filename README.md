# ThreadForge

ThreadForge is a lightweight, customizable thread board engine for posts, media, and community archives. It started as a modernization of a legacy image board workflow and is being generalized for broader self-hosted board use.

## Repository Contents

- `client/`: React, TypeScript, Vite frontend
- `server/`: PHP API, SQLite storage, PHPUnit tests
- `docs/`: architecture, API, DB, and testing notes
- `ThreadForge_Spec.md`: current product specification

Legacy CGI files and historical image/log data are kept out of this Git repository by default. They are useful as migration/reference material, but they are not required to run the current app.

## Local Setup

Frontend:

```powershell
cd client
copy .env.example .env
npm install
npm run dev -- --host 127.0.0.1 --port 5173
```

Backend:

```powershell
cd server
composer install
$env:DOTEITA_ADMIN_PASSWORD='admin'
php -S 127.0.0.1:8000 -t .
```

Then open:

```text
http://127.0.0.1:5173
```

## Tests

Frontend:

```powershell
cd client
npm test -- --run
npm run build
```

Backend:

```powershell
cd server
vendor/bin/phpunit
```

## Versioning

The project version is tracked in:

- `VERSION`
- `CHANGELOG.md`
- `client/package.json`
- `client/src/version.ts`

Use semantic versioning. Update all version references and the changelog in the same commit.

## Runtime Data

The backend creates runtime files locally:

- `server/database.sqlite`
- `server/storage/data/*`

These files are intentionally ignored by Git. Use the admin backup/export feature to move live data between environments.

## Documentation Map

- `ThreadForge_Spec.md`: current product specification and implementation status
- `CHANGELOG.md`: release history
- `docs/README.md`: documentation index
- `docs/API.md`: PHP API reference
- `docs/DB.md`: SQLite/runtime data, backup, import, and reset notes
- `docs/MIGRATION.md`: old BBSnote log import notes
- `docs/ARCHITECTURE.md`: architecture notes
- `docs/TESTING.md`: test strategy
