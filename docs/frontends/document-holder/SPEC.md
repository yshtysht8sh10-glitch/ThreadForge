# document-holder / Document Holder Spec

[Back to specification index](../../SPEC.md)

## Purpose

`document-holder` is a document holder for articles, HTML folders, and production notes that should be read inside the site.

It follows the same organization model as `materials-library`: entries are grouped by tag and author, but the primary content is article text rather than downloadable material.

## List View

- Shows the configured title, description, and description banners.
- Multiple description banners can be registered and cross-fade about every five seconds.
- The update history shows every article in a fixed-height scrolling area sized for about five rows.
- The table of contents groups articles by primary tag, secondary tag, author, and title.
- Users can switch the TOC parent between tag and author on the list page.
- The admin settings and logged-in user profile can define the default TOC parent.
- Draft articles show a red draft label in both the list and article views.

## Posting

- Posts can be written in the browser or uploaded as a ZIP containing `index.html`.
- The browser editor is a single WYSIWYG-like surface with controls for headings, bold text, links, tables, text color, table background color, and image insertion.
- In-progress work can be saved to browser local storage.
- Authors choose a primary tag and either choose or create a secondary tag.
- Primary tags are managed only by administrators.
- The default primary tags are `指南`, `感想文`, `ドキュメント`, and `その他`.

## Article View

- Uploaded or written HTML is rendered as a full article page.
- Opening an article increments its view count, and the article page displays that count.
- During legacy migration, article images and CSS are preserved in the article ZIP and the display HTML is rewritten so those assets remain readable.

## Administration

- Admins can manage the title, description, description banners, default TOC parent, tags, terms, and design.
- Admin analytics show total articles, total views, and author counts.
- User analytics show the logged-in user's article count and total views.

## Legacy Migration

- `tools/migrate_document_holder_runtime.php` imports old `04_DMF` articles into the local runtime DB.
- Old categories are imported as secondary tags under the `指南` primary tag.
- Article dates use the source HTML file modification time.
- Each article is stored as a ZIP containing `index.html` and related assets under `storage/data/`.

## Current Status

Implemented as a React/Vite frontend backed by the common `material_items` API.
