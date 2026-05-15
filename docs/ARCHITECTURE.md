# Architecture

[Japanese architecture notes](ja/ARCHITECTURE.md)

## Current Structure

```text
client/ React + TypeScript + Vite
  |
  | HTTP JSON / multipart form-data
  v
server/api.php
  |
  +-- server/db.php
  |     +-- server/database.sqlite
  |
  +-- server/storage/data/
```

## Frontend

`client/src/App.tsx` defines routing.

- `/`: `HomePage`
- `/thread/:id`: `ThreadPage`
- `/post`: `PostFormPage`
- `/search`: `SearchPage`
- `/edit/:id`: `EditPostPage`

API calls are centralized in `client/src/api.ts`.

When `VITE_USE_MOCK` is `true` or unset, the frontend uses mock responses. Set `VITE_USE_MOCK=false` to use the real PHP API.

## Backend

`server/api.php` dispatches behavior by the `action` parameter.

Main actions:

- list threads
- get thread
- get post
- search
- create post
- update post
- delete post
- RSS feed
- admin list/restore deleted posts
- SNS reaction refresh

DB connection, table creation, image storage helpers, and JSON responses live in `server/db.php`.

## Data Flow

New top-level post:

1. The frontend sends `multipart/form-data`.
2. The API validates required fields.
3. The password is hashed.
4. The post row is saved to SQLite and receives an ID.
5. If an image exists, it is stored under `server/storage/data/` using the post ID.
6. The image path is stored on the post row.
7. If SNS posting is enabled, the API posts to enabled platforms and stores destination IDs/URLs.
8. JSON success or error is returned.

Image replacement:

1. The frontend sends post ID, password, and replacement image.
2. The API validates the password.
3. If the existing image uses the same final filename, it is archived first.
4. The new image is stored as `postId.ext`.
5. The post row `image_path` is updated.

Thread display:

1. The frontend calls `getThread`.
2. The API fetches the top-level post.
3. Replies with the same `thread_id` are fetched by ascending creation time.
4. `{ thread, replies }` is returned.

Delete:

1. The frontend sends post ID and password.
2. The API validates the password.
3. Top-level delete sets `deleted_at` on the thread and replies.
4. Reply delete sets `deleted_at` only on that reply.
5. Rows and image files remain for restore/audit.

Display:

- List, thread, and search APIs return only rows where `deleted_at IS NULL`.
- Deleted posts disappear from normal UI.
- Internal data remains available for restore and inspection.

## SNS Posting

- X, Bluesky, Mastodon, and Misskey are OFF by default.
- The admin page has separate setting groups for X, Bluesky, Mastodon, and Misskey.
- Credential fields are disabled while the corresponding platform is OFF.
- Post forms show read-only previews for enabled platforms.
- Text after `_TWEND_` is excluded from SNS posting.
- Posts with SNS transfer OFF are saved locally and not sent to external SNS.
- Top-level posts with images send the image to X, Bluesky, Mastodon, and Misskey when enabled.
- Long text is shortened with `..` instead of blocking submission.
- Current default limits are X 280, Bluesky 300, Mastodon 500, and Misskey 3000.
- X uses weighted character counting; the other platforms use plain character length.
- SNS text includes a "latest is here" link to the board-list anchor for the post.
- Editing a top-level post does not update or repost to SNS. Replies never use SNS posting.
- Reaction refresh can be run from the admin maintenance tab, `server/cron.php`, or an API-key-protected external scheduler URL.
- Automatic reaction refresh targets only non-deleted top-level posts created within the last 7 days.
- Displayed metrics are X impressions/likes/reposts, Bluesky likes/reposts/quotes, Mastodon boosts/favorites, and Misskey reaction buckets.

## Safe Text Display

- React rendering treats HTML tags as text.
- `LinkedText` turns URLs into links.

## Laravel

`server/laravel/` is a future migration placeholder. Routes, models, controllers, and migrations exist, but the current app's standard API is not the Laravel implementation.

Full Laravel migration is not implemented yet.

## Not Implemented

- CSRF protection
- Body and metadata edit history
