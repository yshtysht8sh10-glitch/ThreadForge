# ThreadForge Specification Index

[Japanese specification index](ja/SPEC.md)

ThreadForge is moving toward one common backend with five standalone frontend applications. Shared backend contracts stay in the common docs, while frontend behavior is documented per frontend.

## Frontend Specs

- [image-board spec](frontends/image-board/SPEC.md)
- [file-uploader spec](frontends/file-uploader/SPEC.md)
- [document-holder spec](frontends/document-holder/SPEC.md)
- [proxy-release spec](frontends/proxy-release/SPEC.md)
- [materials-library spec](frontends/materials-library/SPEC.md)

## Shared Contracts

- [API](API.md)
- [DB runtime index](DB.md)
- [Backend/frontend architecture](ARCHITECTURE.md)
- [Frontend architecture](FRONTEND_ARCHITECTURE.md)
- [Testing](TESTING.md)

## Current Status

- `image-board` is the mature production frontend and contains the detailed current feature specification.
- `file-uploader` 1.0.0 is the first completed standalone frontend after `image-board`.
- `document-holder`, `proxy-release`, and `materials-library` are early frontend applications based on their `legacy/` references.
- The backend code is shared, but runtime DBs and storage are isolated per frontend in development and per release ZIP in deployment.

