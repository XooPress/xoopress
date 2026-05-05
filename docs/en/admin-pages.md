# Managing Pages

Pages are static content entries that are not time-based. They are ideal for "About", "Contact", or other evergreen content.

## Listing Pages

Navigate to `/admin/pages` to see all pages. The interface is similar to posts but filtered to show only entries with `type = 'page'`.

## Creating a Page

1. Go to `/admin/pages/new`
2. Fill in the fields (same as posts):

| Field | Description |
|-------|-------------|
| **Title** | The page headline (required) |
| **Slug** | URL-friendly version (auto-generated if left empty) |
| **Content** | The main body (HTML, Markdown, Visual, or PHP) |
| **Excerpt** | A short summary for listings |
| **Status** | Draft or Published |
| **Language** | Content language (en_US, de_DE, fr_FR) |

3. Choose your editor mode (Visual, HTML, Markdown, or PHP)
4. Click **Save**

## Editing a Page

1. Go to `/admin/pages`
2. Click the page title or the **Edit** link
3. Modify the fields
4. Click **Save**

## Differences from Posts

| Aspect | Posts | Pages |
|--------|-------|-------|
| Time-based | Yes | No |
| Categories | Yes | No |
| Archives | Yes | No |
| URL pattern | `/posts/{id}` | Custom routing needed |
| Use case | Blog, news | About, contact, landing |

## Role-Based Access

| Role | Can create/edit pages? |
|------|-----------------------|
| **Admin** | ✅ |
| **Editor** | ✅ |
| **Author** | ❌ |
| **Subscriber** | ❌ |