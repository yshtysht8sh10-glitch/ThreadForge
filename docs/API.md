# API Reference

[Japanese API reference](ja/API.md)

The current API is implemented in `server/api.php`. It is not REST-shaped; requests are routed by the `action` parameter.

## Common Behavior

- Response format: JSON unless otherwise noted
- Encoding: UTF-8
- CORS: `Access-Control-Allow-Origin: *`
- Allowed methods: `GET`, `POST`, `OPTIONS`
- Error responses use HTTP 400/403/404 and `{ "success": false, "message": "..." }`

## GET `?action=listThreads`

Returns top-level posts.

- Target: `parent_id = 0`
- Order: `created_at DESC`
- Supports `page` and `limit`; maximum `limit` is 100
- Response: `Post[]`

## GET `?action=getThread&id={id}`

Returns a thread and its replies.

- `id`: top-level post ID
- Response: `{ "thread": Post, "replies": Post[] }`
- Replies are ordered by `created_at ASC`

## GET `?action=getPost&id={id}`

Returns one post.

- `id`: post ID
- Response: `Post`

## GET `?action=search&q={query}`

Searches posts.

- Target columns: `title`, `message`, `name`
- Search method: SQLite `LIKE`
- Empty query returns `[]`
- Order: `created_at DESC`
- Supports `page` and `limit`; maximum `limit` is 100
- Response: `Post[]`

## POST `?action=createPost`

Creates a new top-level post or reply.

Content-Type:

- `multipart/form-data`

Fields:

- `name`: required
- `url`: optional
- `title`: required
- `message`: required
- `password`: required
- `thread_id`: optional. New top-level posts use `0` or omit it
- `parent_id`: optional. New top-level posts use `0` or omit it
- `file`: optional. Allowed formats are configured by the admin from GIF, PNG, JPEG, JPG, and BMP. Top-level posts only
- `gdgd`: optional. Top-level posts only
- `tweet_off`: optional. Top-level posts only. UI label is "SNS transfer OFF"
- `source_url`: optional. Top-level posts only. Board-list anchor URL for SNS text. If omitted, the API derives it from the request origin

Notes:

- Replies do not use image upload, gdgd, or SNS posting.
- If SNS posting is enabled for a top-level post, the API posts to enabled SNS platforms from admin settings.
- If a top-level post has an image, X, Bluesky, Mastodon, and Misskey receive the image when enabled.
- SNS text uses only content before `_TWEND_` and is shortened with `..` to fit each platform limit.
- SNS text includes a "latest is here" board-list anchor URL. The frontend sends a temporary `#post-000000` URL, and the API replaces `000000` after the real post ID is saved.

Success response:

```json
{
  "success": true,
  "message": "..."
}
```

## POST `?action=updatePost`

Updates an existing post. The original post password is required.

Content-Type:

- `multipart/form-data`

Fields:

- `id`: required
- `name`: required
- `url`: optional
- `title`: required
- `message`: required
- `password`: required
- `file`: optional. Replaces the image only when supplied. Previous images are archived instead of overwritten
- `gdgd`: optional. Top-level posts only
- `tweet_off`: optional. Top-level posts only. UI label is "SNS transfer OFF"

Password mismatch returns HTTP 403.

Post updates affect only the board. Even for top-level posts, existing SNS posts are not edited and no repost is made. Replies never use SNS posting.

## POST `?action=deletePost`

Soft-deletes a post. The original post password is required.

Content-Type:

- `multipart/form-data`

Fields:

- `id`: required
- `password`: required

Behavior:

- SQLite rows are not removed; `deleted_at` is set.
- Deleting a top-level thread also sets `deleted_at` on its replies.
- Deleting a reply affects only that reply.
- Image files are not deleted.
- List, thread, and search APIs do not return soft-deleted posts.

## GET `?action=rss`

Returns RSS 2.0 XML with up to 30 latest non-deleted top-level posts.

## GET `?action=refreshSocialReactions&admin_password={password}`

Admin-only action that fetches reaction counts from stored SNS post URLs/IDs and caches them.

Targets are all non-deleted top-level posts that have SNS post IDs.

Fetched metrics:

- X: impressions, likes, reposts
- Bluesky: likes, reposts, quotes
- Mastodon: boosts, favorites
- Misskey: reaction buckets

## GET `?action=cronRefreshSocialReactions&api_key={key}`

Scheduler-friendly reaction refresh endpoint for external periodic jobs.

Use the `cronApiKey` shown in the admin screen. This endpoint is intended for GitHub Actions or external HTTP schedulers.

Targets are the same as `refreshSocialReactions`: all non-deleted top-level posts that have SNS post IDs.

For local server Cron, register the `server/cron.php` file path shown in the admin screen.
Opening `cron.php` without a key is rejected. External URL-style schedulers may call `cron.php?api_key={key}`; successful runs are logged as `cron web social reaction ...`.

## GET `?action=listDeletedPosts&admin_password={password}`

Admin-only action that returns soft-deleted posts. `admin_password` must match the configured admin password or the optional `THREADFORGE_ADMIN_PASSWORD` environment variable.

## GET `?action=listAnalyticsPosts&admin_password={password}`

Admin-only action that returns all non-deleted top-level posts for the analytics screen. The response uses the normal `Post` shape, including cached social reaction totals.

## POST `?action=restorePost`

Admin-only action that restores a soft-deleted post.

Fields:

- `id`: required
- `admin_password`: required

## POST `?action=purgePostData`

Admin-only action that erases the content of a deleted post/reply while preserving the display number.

Fields:

- `id`: required
- `admin_password`: required

Behavior:

- For a top-level post, erases content, image reference, SNS metadata, view count, edit revisions, user claims, and replies.
- For a reply, erases the reply content, edit revisions, and user claims.
- The post row remains, so the top-level display number is preserved.

## POST `?action=destroyPostNumber`

Admin-only action that physically removes a post/reply row.

Fields:

- `id`: required
- `admin_password`: required

Behavior:

- For a top-level post, physically removes the thread and its replies.
- Removing a top-level post this way compacts later top-level display numbers.

## GET `?action=listAdminUsers&admin_password={password}`

Admin-only action that returns registered users for maintenance.

Response:

- `success`: true
- `users`: admin user records

## POST `?action=adminUpdateUser`

Admin-only action that updates a registered user.

Fields:

- `id`: required
- `admin_password`: required
- `login_id`: required
- `display_name`: required, up to 30 characters
- `post_password`: optional, truncated to 8 characters if longer
- `home_url`: optional
- `login_password`: optional. When present, resets the login password

## POST `?action=adminDeleteUser`

Admin-only action that erases or deletes a registered user.

Fields:

- `id`: required
- `stage`: `1` or `2`
- `admin_password`: required

Behavior:

- `stage=1`: erases personal user information and sessions while preserving the user number.
- `stage=2`: physically deletes the user row and compacts remaining user numbers.

## Post Type

```ts
type Post = {
  id: number;
  display_no?: number;
  reply_no?: number;
  thread_id: number;
  parent_id: number;
  name: string;
  url?: string | null;
  title: string;
  message: string;
  image_path?: string | null;
  created_at: string;
  deleted_at?: string | null;
  tweet_off?: boolean;
  tweet_text?: string | null;
  tweet_url?: string | null;
  tweet_like_count?: number;
  tweet_retweet_count?: number;
  tweet_comment_count?: number;
  tweet_impression_count?: number;
  social_links?: {
    x?: string | null;
    bluesky?: string | null;
    mastodon?: string | null;
    misskey?: string | null;
  };
  social_reactions?: {
    x?: { likes: number; reposts: number; impressions: number };
    bluesky?: { likes: number; reposts: number; quotes: number };
    mastodon?: { boosts: number; favs: number };
    misskey?: {
      fire: number;
      eyes: number;
      cry: number;
      thinking: number;
      party: number;
      other: number;
    };
  };
  replies?: Post[];
  reply_count?: number;
};
```

`password_hash` is never included in API responses.

## Laravel API

`server/laravel/routes/api.php` contains Laravel routes, but the current frontend does not use them as the standard API. The Laravel API is not treated as production-ready yet.

## Non-Web Import Operation

Local Archive `LOG_*.cgi` import is not exposed through the web API or admin screen. Run it from a local operator batch or PHP command that directly calls `importLocalArchiveDirectory()` in `server/db.php`.
