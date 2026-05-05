# DotEita TypeScript Board

DotEita TypeScript Board is a React + TypeScript SPA with a small PHP + SQLite backend. It is a modernized implementation of the DotEita image board workflow.

## Repository Contents

- `client/`: React, TypeScript, Vite frontend
- `server/`: PHP API, SQLite storage, PHPUnit tests
- `docs/`: architecture, API, DB, and testing notes
- `DotEitaTypeScript_Spec.md`: current product specification

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

## Runtime Data

The backend creates runtime files locally:

- `server/database.sqlite`
- `server/storage/data/*`

These files are intentionally ignored by Git. Use the admin backup/export feature to move live data between environments.
