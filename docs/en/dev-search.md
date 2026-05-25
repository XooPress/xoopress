# Full-Text Search

XooPress provides a built-in full-text search engine with automatic indexing and smart ranking.

## Architecture

The search engine uses a **dual-mode architecture**:
- **MySQL FULLTEXT index** (primary) — Boolean-mode search with term wildcards for fast, ranked results
- **LIKE-based fallback** — Automatic fallback when FULLTEXT indexes are not available or supported

## Database Schema

### `xp_search_index` Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Unique entry ID |
| `content_type` | VARCHAR(50) | Type of content (e.g., `post`, `page`) |
| `content_id` | INT | ID of the indexed content |
| `title` | VARCHAR(255) | Content title (FULLTEXT indexed) |
| `content` | LONGTEXT | Content body (FULLTEXT indexed, HTML stripped) |
| `excerpt` | TEXT | Content excerpt (FULLTEXT indexed) |
| `meta_data` | JSON | Additional metadata for filtering |
| `indexed_at` | DATETIME | When this entry was last indexed |
| Index | UNIQUE(content_type, content_id) | Prevents duplicate entries |

### FULLTEXT Indexes

- `ft_search` on `(title, content, excerpt)` in `xp_search_index`
- `ft_posts_search` on `(title, content)` in `xp_posts` table

## Indexing

### Automatic Indexing

Content is automatically indexed when:
- A post is created or updated
- A post is published
- A post is deleted (removed from index)

### Manual Rebuild

Rebuild the entire search index from the command line:

```bash
php xps search:rebuild
```

Or from the admin panel: **Admin → Settings → Search → Rebuild Index**

The `rebuildIndex()` method:
1. Truncates the entire `xp_search_index` table
2. Re-indexes all posts with status `published`, `draft`, `pending`, `pending_review`, or `approved`
3. Attaches metadata: status, author_id, category_id, language, type

### Programmatic Indexing

```php
use XooPress\Core\Search;

$search = $container->get('search');

// Index a content item
$search->index('post', $postId, $title, $content, $excerpt, ['status' => 'published']);

// Remove from index
$search->remove('post', $postId);
```

## Searching

### Basic Search

```php
$results = $search->search('search term', $page, $perPage);
```

### Advanced Search Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `query` | string | The search query |
| `content_type` | string | Filter by content type (e.g., `post`, `page`) |
| `status` | string | Filter by status |
| `author_id` | int | Filter by author |
| `category_id` | int | Filter by category |
| `page` | int | Page number (default: 1) |
| `per_page` | int | Results per page (default: 10) |

### Response Format

```php
[
    'results' => [
        [
            'content_type' => 'post',
            'content_id' => 1,
            'title' => 'Hello World',
            'excerpt' => 'Welcome to <mark>XooPress</mark>.',
            'score' => 5.2,
            'url' => '/hello-world',
        ],
    ],
    'total' => 1,
    'page' => 1,
    'per_page' => 10,
    'pages' => 1,
]
```

### Search Features

- **Highlighting** — Matching terms are wrapped in `<mark>` tags in excerpts
- **Excerpt extraction** — Automatic excerpt generation with surrounding context
- **Boolean mode** — Supports `+term` (required) and `-term` (excluded) syntax
- **Prefix wildcards** — `term*` matches word prefixes
- **Pagination** — Results are paginated with total count

## Front-end Search

Access the search page at `/search?q=your+query`. The search view (`modules/Content/views/search.php`) displays:

- Search input with current query
- Number of results found
- Paginated result list with titles, excerpts, and links
- Highlighted search terms in excerpts
- Previous/next pagination navigation

## Admin Search Management

### Rebuild Index

In the admin panel, navigate to **Admin → Settings → Search** to:
- View the current search index status (total indexed items)
- Click **"Rebuild Search Index"** to regenerate the index
- Monitor progress during rebuild

### Autocomplete

The search system supports autocomplete suggestions for the front-end search input, providing instant results as users type.

## Performance Considerations

- FULLTEXT search on properly indexed tables is extremely fast, even on large datasets
- The LIKE fallback uses `content LIKE '%term%'` which performs table scans on large tables
- Rebuilding the index is recommended after bulk imports or content migrations
- The search index table is separate from the main content tables, keeping read/write operations isolated