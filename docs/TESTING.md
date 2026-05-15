# Testing

[Japanese testing notes](ja/TESTING.md)

## Frontend

Tools:

- Vitest
- React Testing Library
- jsdom

Main tests:

- `client/src/api.test.ts`
- `client/src/pages/HomePage.test.tsx`
- `client/src/pages/PostFormPage.test.tsx`
- `client/src/pages/ThreadPage.test.tsx`
- `client/src/pages/AdminPage.test.tsx`
- `client/src/tweet.test.ts`

Run:

```powershell
cd client
npm test
```

## Backend

Tools:

- PHPUnit 9

Main tests:

- `server/tests/StorageLayerTest.php`
- `server/tests/ApiHttpIntegrationTest.php`

Covered behavior:

- SQLite connection and table initialization
- Image storage directory initialization
- `buildPost()` image URL conversion
- `normalizeString()` trimming
- `findPostById()` lookup
- `findActivePostById()` excluding soft-deleted posts
- `deleteImage()` behavior
- `archiveExistingImage()` archive naming
- `saveUploadedImage()` returning `null` for normal files
- SNS text generation, "latest is here" URL, post ID placeholder replacement, X character counting, per-SNS shortening, `_TWEND_`
- SNS image posting for X, Bluesky, Mastodon, and Misskey
- SNS reaction refresh through manual admin action, local Cron, API-key-protected external scheduler, and 7-day target limit
- Non-destructive and duplicate-skipping local archive log import
- Duplicate post detection
- URL normalization
- HTTP integration coverage for `server/api.php`
- Create, update, and delete flows through HTTP
- Delete APIs setting `deleted_at` without physically deleting rows
- Real multipart image upload success path
- Image replacement preserving archived history through HTTP
- Search API empty query, pagination, scope, and escaped wildcard edges
- Error paths for password mismatch and missing posts

Run:

```powershell
cd server
.\.php\php.exe .\vendor\bin\phpunit
```

## Laravel Skeleton

Additional Laravel-side feature tests are scaffolded under:

- `server/laravel/tests/Feature/PostApiTest.php`

Run after installing the Laravel skeleton dependencies:

```powershell
cd server\laravel
composer install
vendor\bin\phpunit
```
