# Content Staging & Previews

XooPress provides a content staging system with shareable preview links for reviewing content before publication.

## Overview

The staging system allows you to:
- **Stage content** — Save draft revisions of posts without affecting the live version
- **Generate preview links** — Create cryptographically secure, time-limited preview URLs
- **Publish staged content** — Apply staged changes to live posts
- **Preview new content** — Create preview tokens for brand new content before it exists as a post

## Database Schema

### `xp_preview_tokens` Table

| Column | Type | Description |
|--------|------|-------------|
| `token` | CHAR(64) PK | 64-character hex token (bin2hex(random_bytes(32))) |
| `content_type` | VARCHAR(50) | Type of content being previewed |
| `content_id` | INT | ID of the content (NULL for new content) |
| `staged_id` | INT (FK → xp_content_staging.id) | Reference to staged draft |
| `created_by` | INT | User who created the token |
| `expires_at` | DATETIME | Expiration timestamp (72 hours) |
| Index | UNIQUE(content_id, created_by) | One token per content+user combo |

### `xp_content_staging` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Unique staging entry ID |
| `content_id` | INT | ID of the target post (NULL for new content) |
| `title` | VARCHAR(255) | Staged title |
| `content` | LONGTEXT | Staged content body |
| `excerpt` | TEXT | Staged excerpt |
| `slug` | VARCHAR(200) | Staged URL slug |
| `status` | VARCHAR(50) | Default: `staged` |
| `meta_data` | JSON | Additional metadata (category_id, language, etc.) |
| `author_id` | INT | Who created the staging entry |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

## Key Features

### Preview Tokens

- **Cryptographically secure** — 64-character hex tokens generated via `bin2hex(random_bytes(32))`
- **72-hour expiry** — Tokens automatically expire; new tokens invalidate old ones for the same content+user
- **Shareable links** — URLs like `/preview/{token}` can be shared with reviewers

### Preview Flow

1. Author stages changes to a post (or creates new content)
2. Admin generates a preview token
3. A shareable link is sent to reviewers
4. Reviewers access the preview page without authentication
5. If approved, staged content is published to the live site

## Admin UI

Access staging management at:
- Individual post edit page → **"Generate Preview Link"** button
- `/admin/staging` — Full staging dashboard

### Staging Dashboard

- **List all staged content** with status indicators
- **Preview** — View staged content via shareable link
- **Publish** — Apply staged changes to the live post
- **Discard** — Discard staged changes
- **Generate Token** — Create new preview tokens

## API

### Generate a Preview Token

```php
use XooPress\Core\Staging;

$staging = $container->get('staging');

// For existing content
$result = $staging->generatePreviewToken('post', $postId, $userId);
// Returns: ['token' => '...', 'expires_at' => '...', 'preview_url' => '/preview/...']

// For new content with staged draft
$result = $staging->generatePreviewToken('post', null, $userId, $stagedId);
```

### Stage Content

```php
$staging->stage($postId, [
    'title' => 'Updated Title',
    'content' => 'Updated content...',
    'excerpt' => 'Updated excerpt',
    'slug' => 'updated-slug',
    'meta_data' => ['category_id' => 5],
], $userId);
```

### Publish Staged Content

```php
$staging->publish($stagingId, $userId);
```

Applies the staged changes to the post and triggers search re-indexing.

### Verify a Preview Token

```php
$data = $staging->verifyPreviewToken($token);
// Returns post data if token is valid and not expired, null otherwise
```

## Use Cases

- **Editorial workflow** — Editors review content before publication
- **Client approvals** — Share preview links with clients for feedback
- **Content collaboration** — Multiple authors can stage changes independently
- **Scheduled promotions** — Prepare content in advance and publish when ready